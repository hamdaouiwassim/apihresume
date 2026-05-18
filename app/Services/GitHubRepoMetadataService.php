<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GitHubRepoMetadataService
{
    private const MAX_DESCRIPTION = 4500;

    private const MAX_README_FALLBACK = 3500;

    private const MAX_TECHNOLOGIES = 500;

    /**
     * Parse a GitHub repository URL or "owner/repo" string into [owner, repo].
     * Rejects values that cannot be used safely for outbound requests (SSRF mitigation).
     *
     * @return array{owner: string, repo: string}
     */
    public function parseOwnerAndRepo(string $raw): array
    {
        $s = trim($raw);
        if ($s === '') {
            throw new HttpException(422, 'Repository URL is required.');
        }

        $s = rtrim($s, '/');

        if (preg_match('~^https?://(?:www\.)?github\.com/([^/]+)/([^/?#]+)$~i', $s, $m)) {
            $owner = rawurldecode($m[1]);
            $repo = rawurldecode($m[2]);
        } elseif (preg_match('#^([^/]+)/([^/]+)$#', $s, $m)) {
            $owner = $m[1];
            $repo = $m[2];
        } else {
            throw new HttpException(422, 'Use a GitHub URL like https://github.com/owner/repo or owner/repo.');
        }

        $repo = preg_replace('#\.git$#i', '', $repo) ?? $repo;

        if (! $this->isValidGitHubLogin($owner)) {
            throw new HttpException(422, 'Invalid repository owner.');
        }

        if (! $this->isValidRepoName($repo)) {
            throw new HttpException(422, 'Invalid repository name.');
        }

        return ['owner' => $owner, 'repo' => $repo];
    }

    /**
     * Fetch repository metadata and map to resume project draft fields.
     *
     * Uses the user's OAuth token when provided, otherwise the optional server GITHUB_TOKEN, otherwise anonymous (public repos only).
     *
     * @return array{name: string, description: string|null, technologies: string|null, url: string, startDate: string|null, endDate: null}
     */
    public function buildProjectDraftFromRepo(string $owner, string $repo, ?string $userAccessToken = null): array
    {
        $repoResponse = $this->githubClient($userAccessToken)->get($this->repoApiUrl($owner, $repo));

        if ($repoResponse->status() === 404) {
            throw new HttpException(404, 'Repository not found or not accessible. Connect GitHub in your profile for private repositories, or ask your admin to set GITHUB_TOKEN.');
        }

        if ($repoResponse->status() === 403) {
            throw new HttpException(403, 'GitHub denied access (rate limit or private repository).');
        }

        if ($repoResponse->failed()) {
            throw new HttpException(422, 'Could not load repository from GitHub. Try again later.');
        }

        /** @var array<string, mixed> $repoJson */
        $repoJson = $repoResponse->json();

        $name = (string) ($repoJson['name'] ?? $repo);
        $htmlUrl = (string) ($repoJson['html_url'] ?? "https://github.com/{$owner}/{$repo}");
        $description = isset($repoJson['description']) && is_string($repoJson['description'])
            ? trim($repoJson['description'])
            : '';

        if ($description === '' && ! empty($repoJson['default_branch'])) {
            $readmeText = $this->fetchReadmePlain($owner, $repo, $userAccessToken);
            if ($readmeText !== null && $readmeText !== '') {
                $description = mb_substr($readmeText, 0, self::MAX_README_FALLBACK);
            }
        }

        if ($description !== '') {
            $description = mb_substr($description, 0, self::MAX_DESCRIPTION);
        } else {
            $description = null;
        }

        $technologies = $this->fetchLanguagesSummary($owner, $repo, $userAccessToken);

        $createdAt = $repoJson['created_at'] ?? null;
        $startDate = null;
        if (is_string($createdAt) && $createdAt !== '') {
            try {
                $startDate = Carbon::parse($createdAt)->toDateString();
            } catch (\Throwable) {
                $startDate = null;
            }
        }

        return [
            'name' => mb_substr($name, 0, 255),
            'description' => $description,
            'technologies' => $technologies,
            'url' => mb_substr($htmlUrl, 0, 255),
            'startDate' => $startDate,
            'endDate' => null,
        ];
    }

    private function repoApiUrl(string $owner, string $repo): string
    {
        return 'https://api.github.com/repos/'.rawurlencode($owner).'/'.rawurlencode($repo);
    }

    private function githubClient(?string $userAccessToken = null): PendingRequest
    {
        $req = Http::timeout(15)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'HResume-App/1.0',
            ]);

        if (filled($userAccessToken)) {
            return $req->withToken($userAccessToken);
        }

        $token = config('services.github.token');
        if (filled($token)) {
            return $req->withToken($token);
        }

        return $req;
    }

    private function isValidGitHubLogin(string $owner): bool
    {
        if (strlen($owner) < 1 || strlen($owner) > 39) {
            return false;
        }

        if (strlen($owner) === 1) {
            return (bool) preg_match('/^[a-zA-Z0-9]$/', $owner);
        }

        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$/', $owner);
    }

    private function isValidRepoName(string $repo): bool
    {
        if (strlen($repo) < 1 || strlen($repo) > 100) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z0-9._-]+$/', $repo);
    }

    private function fetchLanguagesSummary(string $owner, string $repo, ?string $userAccessToken = null): ?string
    {
        $url = $this->repoApiUrl($owner, $repo).'/languages';
        $response = $this->githubClient($userAccessToken)->get($url);

        if ($response->failed()) {
            return null;
        }

        /** @var array<string, int|float> $langs */
        $langs = $response->json();
        if (! is_array($langs) || $langs === []) {
            return null;
        }

        arsort($langs, SORT_NUMERIC);
        $names = array_keys($langs);
        $joined = implode(', ', $names);

        return mb_substr($joined, 0, self::MAX_TECHNOLOGIES);
    }

    private function fetchReadmePlain(string $owner, string $repo, ?string $userAccessToken = null): ?string
    {
        $url = $this->repoApiUrl($owner, $repo).'/readme';
        $response = $this->githubClient($userAccessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.raw'])
            ->get($url);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $body = $response->body();

        return is_string($body) && $body !== '' ? $body : null;
    }
}
