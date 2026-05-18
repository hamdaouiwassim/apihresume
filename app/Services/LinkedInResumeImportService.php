<?php

namespace App\Services;

use App\Models\BasicInfo;
use App\Models\Experience;
use App\Models\Resume;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LinkedInResumeImportService
{
    public function import(User $user, Resume $resume): array
    {
        $userId = (int) $user->id;

        if (! $resume->canBeEditedBy($userId)) {
            abort(403, 'You cannot edit this resume.');
        }

        $canBasic = $resume->canEditSection($userId, 'basic_info');
        $canExperiences = $resume->canEditSection($userId, 'experiences');

        if (! $canBasic && ! $canExperiences) {
            abort(403, 'You do not have permission to import into this resume.');
        }

        if (blank($user->linkedin_id) && blank($user->linkedin_token)) {
            abort(422, 'Sign in with LinkedIn on your account first, then try importing again.');
        }

        $token = $this->ensureFreshAccessToken($user);
        if (! $token) {
            abort(422, 'Your LinkedIn session has expired. Please sign in with LinkedIn again, then retry the import.');
        }

        $userinfo = $this->fetchUserinfo($token);
        if (! $userinfo) {
            abort(422, 'Could not read your LinkedIn profile. Please sign in with LinkedIn again and retry.');
        }

        $me = $this->fetchMemberMe($token);
        $usedProfileApi = $me !== null;

        $headline = $this->extractLocalizedHeadline($me);
        $vanity = is_array($me) ? ($me['vanityName'] ?? null) : null;

        $experiencesAdded = 0;
        $notes = [];

        if (! $usedProfileApi) {
            $notes[] = 'Full work history is not available with standard LinkedIn Sign In; we applied your OpenID profile (name, email, photo). If your LinkedIn app includes Profile API access, headline-based roles import automatically.';
        }

        if ($canBasic) {
            $this->applyBasicInfo($resume, $user, $userinfo, $headline, $vanity);
        }

        if ($canExperiences && $headline) {
            $experiencesAdded = $this->applyHeadlineExperiences($resume, $headline);
            if ($experiencesAdded === 0) {
                $notes[] = 'No new experience rows were added (headline may not contain recognizable "Role at Company" segments).';
            }
        } elseif ($canExperiences && ! $headline) {
            $notes[] = 'Work history is not available from LinkedIn with standard Sign In permissions; add roles manually or request LinkedIn Profile API access for your app.';
        }

        return [
            'basic_info_updated' => $canBasic,
            'experiences_added' => $experiencesAdded,
            'used_profile_api' => $usedProfileApi,
            'notes' => $notes,
        ];
    }

    private function ensureFreshAccessToken(User $user): ?string
    {
        $token = $user->linkedin_token;
        if (filled($token) && $this->userinfoRequest($token)->successful()) {
            return $token;
        }

        if ($this->refreshLinkedInAccessToken($user)) {
            $user->refresh();

            return $user->linkedin_token;
        }

        return null;
    }

    private function userinfoRequest(string $token): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-RestLi-Protocol-Version' => '2.0.0',
        ])->timeout(15)->get('https://api.linkedin.com/v2/userinfo');
    }

    private function fetchUserinfo(string $token): ?array
    {
        $response = $this->userinfoRequest($token);
        if (! $response->successful()) {
            Log::warning('LinkedIn userinfo failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Optional: r_liteprofile / r_basicprofile style access. Returns null if forbidden or invalid.
     */
    private function fetchMemberMe(string $token): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-RestLi-Protocol-Version' => '2.0.0',
        ])->timeout(15)->get('https://api.linkedin.com/v2/me', [
            'projection' => '(vanityName,localizedHeadline,headline,localizedFirstName,localizedLastName)',
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function refreshLinkedInAccessToken(User $user): bool
    {
        if (blank($user->linkedin_refresh_token)) {
            return false;
        }

        $response = Http::asForm()->timeout(15)->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->linkedin_refresh_token,
            'client_id' => config('services.linkedin-openid.client_id'),
            'client_secret' => config('services.linkedin-openid.client_secret'),
        ]);

        if (! $response->successful()) {
            Log::warning('LinkedIn token refresh failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 400),
            ]);

            return false;
        }

        $data = $response->json();
        $user->forceFill([
            'linkedin_token' => $data['access_token'] ?? null,
            'linkedin_refresh_token' => $data['refresh_token'] ?? $user->linkedin_refresh_token,
        ])->save();

        return filled($data['access_token'] ?? null);
    }

    private function extractLocalizedHeadline(?array $me): ?string
    {
        if (! is_array($me)) {
            return null;
        }

        if (! empty($me['localizedHeadline']) && is_string($me['localizedHeadline'])) {
            return $me['localizedHeadline'];
        }

        $headline = $me['headline'] ?? null;
        if (is_string($headline)) {
            return $headline;
        }

        if (is_array($headline)) {
            $localized = $headline['localized'] ?? [];
            if (is_array($localized)) {
                $first = reset($localized);

                return is_string($first) ? $first : null;
            }
        }

        return null;
    }

    private function applyBasicInfo(Resume $resume, User $user, array $userinfo, ?string $headline, ?string $vanity): void
    {
        $name = $this->toNonEmptyString($userinfo['name'] ?? null);
        $email = $this->toNonEmptyString($userinfo['email'] ?? null);
        $picture = $this->toNonEmptyString($userinfo['picture'] ?? null);
        $locale = $userinfo['locale'] ?? null;

        $existing = BasicInfo::where('resume_id', $resume->id)->first();

        $linkedinUrl = null;
        if (filled($vanity)) {
            $linkedinUrl = 'https://www.linkedin.com/in/'.ltrim($vanity, '/');
        }

        $locationFromLinkedIn = $this->localeClaimToLocationString($locale);

        $defaults = [
            'full_name' => $name ?: ($existing?->full_name ?? $user->name ?? 'Candidate'),
            'email' => $email ?: ($existing?->email ?? $user->email),
            'phone' => $existing?->phone ?: '—',
            'job_title' => $headline ?: ($existing?->job_title ?: 'Professional'),
            'professional_summary' => $existing?->professional_summary ?: 'Add a concise professional summary. Details from LinkedIn (name, email, photo) were merged into this resume.',
            'location' => $this->toNonEmptyString($existing?->location),
            'linkedin' => $linkedinUrl ?: ($existing?->linkedin),
            'github' => $existing?->github,
            'website' => $existing?->website,
            'avatar' => $picture ?: ($existing?->avatar),
        ];

        if ($locationFromLinkedIn !== null && blank($defaults['location'])) {
            $defaults['location'] = $locationFromLinkedIn;
        }

        BasicInfo::updateOrCreate(
            ['resume_id' => $resume->id],
            array_filter($defaults, fn ($v) => ! is_null($v))
        );
    }

    /**
     * @return list<array{position: string, company: string}>
     */
    private function parseHeadlineSegments(string $headline): array
    {
        $segments = preg_split('/\s*\|\s*/u', $headline) ?: [];
        $out = [];

        foreach ($segments as $segment) {
            $segment = trim((string) $segment);
            if ($segment === '') {
                continue;
            }

            $atMarker = ' at ';
            $atPos = mb_strrpos($segment, $atMarker);
            if ($atPos === false) {
                $out[] = ['position' => $segment, 'company' => ''];

                continue;
            }

            $position = trim(mb_substr($segment, 0, $atPos));
            $company = trim(mb_substr($segment, $atPos + mb_strlen($atMarker)));
            if ($position === '') {
                continue;
            }
            $out[] = [
                'position' => $position,
                'company' => $company !== '' ? $company : '—',
            ];
        }

        return $out;
    }

    private function applyHeadlineExperiences(Resume $resume, string $headline): int
    {
        $segments = $this->parseHeadlineSegments($headline);
        $added = 0;
        $placeholderStart = Carbon::now()->subYears(2)->startOfMonth()->toDateString();

        foreach ($segments as $row) {
            $position = $row['position'];
            $company = $row['company'] !== '' ? $row['company'] : '—';

            $dup = $resume->experiences()
                ->whereRaw('LOWER(TRIM(position)) = ?', [mb_strtolower($position)])
                ->whereRaw('LOWER(TRIM(company)) = ?', [mb_strtolower($company)])
                ->exists();

            if ($dup) {
                continue;
            }

            Experience::create([
                'resume_id' => $resume->id,
                'company' => $company,
                'position' => $position,
                'startDate' => $placeholderStart,
                'endDate' => null,
                'description' => 'Imported from your LinkedIn headline. Replace this text with your impact, responsibilities, and metrics. Update dates to match your employment history.',
                'is_present' => false,
            ]);
            $added++;
        }

        return $added;
    }

    /**
     * LinkedIn userinfo "locale" may be a string (e.g. "en-US") or an object with language/country.
     */
    private function localeClaimToLocationString(mixed $locale): ?string
    {
        if (is_string($locale)) {
            $s = trim($locale);

            return $s !== '' ? $s : null;
        }

        if (is_array($locale)) {
            $lang = isset($locale['language']) ? trim((string) $locale['language']) : '';
            $country = isset($locale['country']) ? trim((string) $locale['country']) : '';
            if ($lang !== '' && $country !== '') {
                return strtolower($lang).'_'.strtoupper($country);
            }
            if ($lang !== '') {
                return $lang;
            }
            if ($country !== '') {
                return strtoupper($country);
            }

            return null;
        }

        return null;
    }

    private function toNonEmptyString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $s = trim($value);

            return $s !== '' ? $s : null;
        }
        if (is_scalar($value)) {
            $s = trim((string) $value);

            return $s !== '' ? $s : null;
        }

        return null;
    }
}
