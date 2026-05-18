<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ShareableLink;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShareableLinkController extends Controller
{
    /**
     * Generate a shareable link for a resume
     */
    public function generate(Request $request, $resumeId)
    {
        try {
            $validator = Validator::make([
                'resume_id' => $resumeId,
                'expires_in_days' => $request->input('expires_in_days', 7),
                'slug' => $request->input('slug'),
            ], [
                'resume_id' => 'required|exists:resumes,id',
                'expires_in_days' => 'nullable|integer|min:1|max:365',
                'slug' => ['nullable', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Deactivate any existing active links for this resume
            ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new shareable link
            $expiresInDays = $request->input('expires_in_days', 7);
            $slug = $request->filled('slug') ? strtolower(trim((string) $request->input('slug'))) : null;
            if ($slug && ShareableLink::where('slug', $slug)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This slug is already in use. Please choose another.',
                ], 422);
            }
            if ($slug && Resume::where('public_profile_slug', $slug)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This slug is reserved for a public profile. Please choose another.',
                ], 422);
            }
            $shareableLink = ShareableLink::create([
                'resume_id' => $resumeId,
                'token' => ShareableLink::generateToken(),
                'slug' => $slug,
                'expires_at' => Carbon::now()->addDays($expiresInDays),
                'is_active' => true,
            ]);

            // Generate frontend URL for the shareable link
            // Try to get from env, fallback to common frontend ports
            $frontendUrl = env('FRONTEND_URL');
            if (! $frontendUrl) {
                // Try to detect from request origin
                $requestOrigin = $request->header('Origin') ?? $request->header('Referer');
                if ($requestOrigin) {
                    $parsed = parse_url($requestOrigin);
                    if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                        $frontendUrl = $parsed['scheme'].'://'.$parsed['host'].$port;
                    }
                }
                // Fallback to common frontend dev ports
                $frontendUrl = $frontendUrl ?: 'http://localhost:5173'; // Vite default
            }
            $shareUrl = rtrim($frontendUrl, '/')."/share/{$shareableLink->token}";
            $websiteIdentifier = $shareableLink->slug ?: $shareableLink->token;
            $websiteUrl = rtrim($frontendUrl, '/')."/website/{$websiteIdentifier}";

            return response()->json([
                'status' => true,
                'message' => 'Shareable link generated successfully',
                'data' => [
                    'token' => $shareableLink->token,
                    'slug' => $shareableLink->slug,
                    'url' => $shareUrl,
                    'website_url' => $websiteUrl,
                    'expires_at' => $shareableLink->expires_at->toDateTimeString(),
                    'expires_in_days' => $expiresInDays,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View resume using shareable link (public access)
     */
    public function view($token)
    {
        try {
            $shareableLink = ShareableLink::where('token', $token)->firstOrFail();

            // Check if link is valid
            if (! $shareableLink->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This link has expired or been deactivated',
                ], 410);
            }

            // Load resume with all relationships
            $resume = $shareableLink->resume->load(
                'basicInfo',
                'experiences',
                'educations',
                'skills',
                'hobbies',
                'certificates',
                'languages',
                'template'
            );

            $data = $resume->toArray();
            $basicInfo = $data['basic_info'] ?? $data['basicInfo'] ?? null;
            $data['basic_info'] = is_array($basicInfo) ? $basicInfo : [];
            if (! array_key_exists('avatar', $data['basic_info'])) {
                $data['basic_info']['avatar'] = null;
            }
            if (isset($data['basicInfo'])) {
                unset($data['basicInfo']);
            }

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Link not found or invalid',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * View resume data for personal website using public profile slug, shareable slug, or token (public access)
     */
    public function website($slugOrToken)
    {
        try {
            $key = strtolower(trim((string) $slugOrToken));

            $resume = Resume::query()
                ->where('public_profile_enabled', true)
                ->where('public_profile_slug', $key)
                ->with([
                    'basicInfo',
                    'experiences',
                    'educations',
                    'skills',
                    'hobbies',
                    'certificates',
                    'languages',
                    'projects',
                    'template',
                ])
                ->first();

            if ($resume) {
                $data = $this->normalizeResumePayload($resume);
                $basic = $data['basic_info'] ?? [];
                $defaultTitle = trim(($basic['full_name'] ?? '').' — '.($basic['job_title'] ?? 'Profile'));
                $defaultDesc = Str::limit((string) ($basic['professional_summary'] ?? ''), 200);

                return response()->json([
                    'status' => true,
                    'message' => 'Resume fetched successfully',
                    'data' => $data,
                    'meta' => [
                        'title' => $resume->public_profile_meta_title ?: ($defaultTitle ?: 'Public profile'),
                        'description' => $resume->public_profile_meta_description ?: $defaultDesc,
                        'robots' => 'index, follow',
                        'profile_slug' => $resume->public_profile_slug,
                        'profile_path' => '/u/'.$resume->public_profile_slug,
                    ],
                ], 200);
            }

            $shareableLink = ShareableLink::where('slug', $slugOrToken)
                ->orWhere('token', $slugOrToken)
                ->firstOrFail();

            if (! $shareableLink->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This link has expired or been deactivated',
                ], 410);
            }

            $resume = $shareableLink->resume->load(
                'basicInfo',
                'experiences',
                'educations',
                'skills',
                'hobbies',
                'certificates',
                'languages',
                'projects',
                'template'
            );

            $data = $this->normalizeResumePayload($resume);
            $basic = $data['basic_info'] ?? [];
            $defaultTitle = trim(($basic['full_name'] ?? '').' — CV');
            $defaultDesc = Str::limit((string) ($basic['professional_summary'] ?? ''), 200);

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => $data,
                'meta' => [
                    'title' => $defaultTitle ?: 'Shared resume',
                    'description' => $defaultDesc,
                    'robots' => 'noindex, nofollow',
                    'profile_slug' => null,
                    'profile_path' => null,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Link not found or invalid',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    private function normalizeResumePayload(Resume $resume): array
    {
        $data = $resume->toArray();
        $basicInfo = $data['basic_info'] ?? $data['basicInfo'] ?? null;
        $data['basic_info'] = is_array($basicInfo) ? $basicInfo : [];
        if (! array_key_exists('avatar', $data['basic_info'])) {
            $data['basic_info']['avatar'] = null;
        }
        if (isset($data['basicInfo'])) {
            unset($data['basicInfo']);
        }

        return $data;
    }

    /**
     * Deactivate a shareable link
     */
    public function deactivate(Request $request, $resumeId)
    {
        try {
            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Deactivate all active links for this resume
            ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return response()->json([
                'status' => true,
                'message' => 'Shareable link deactivated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current shareable link for a resume
     */
    public function getCurrentLink(Request $request, $resumeId)
    {
        try {
            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $shareableLink = ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (! $shareableLink) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active shareable link found',
                    'data' => null,
                ], 404);
            }

            // Generate frontend URL for the shareable link
            // Try to get from env, fallback to common frontend ports
            $frontendUrl = env('FRONTEND_URL');
            if (! $frontendUrl) {
                // Try to detect from request origin
                $requestOrigin = $request->header('Origin') ?? $request->header('Referer');
                if ($requestOrigin) {
                    $parsed = parse_url($requestOrigin);
                    if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                        $frontendUrl = $parsed['scheme'].'://'.$parsed['host'].$port;
                    }
                }
                // Fallback to common frontend dev ports
                $frontendUrl = $frontendUrl ?: 'http://localhost:5173'; // Vite default
            }
            $shareUrl = rtrim($frontendUrl, '/')."/share/{$shareableLink->token}";
            $websiteIdentifier = $shareableLink->slug ?: $shareableLink->token;
            $websiteUrl = rtrim($frontendUrl, '/')."/website/{$websiteIdentifier}";

            return response()->json([
                'status' => true,
                'message' => 'Shareable link retrieved successfully',
                'data' => [
                    'token' => $shareableLink->token,
                    'slug' => $shareableLink->slug,
                    'url' => $shareUrl,
                    'website_url' => $websiteUrl,
                    'expires_at' => $shareableLink->expires_at->toDateTimeString(),
                    'is_active' => $shareableLink->is_active,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
