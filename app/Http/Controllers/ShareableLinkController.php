<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ShareableLink;
use App\Models\Resume;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ShareableLinkController extends Controller
{
    /**
     * Generate a shareable link for a resume
     */
    public function generate(Request $request, $resumeId)
    {
        try {
            $validator = Validator::make(['resume_id' => $resumeId, 'expires_in_days' => $request->input('expires_in_days', 7)], [
                'resume_id' => 'required|exists:resumes,id',
                'expires_in_days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $resume = Resume::findOrFail($resumeId);

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Deactivate any existing active links for this resume
            ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new shareable link
            $expiresInDays = $request->input('expires_in_days', 7);
            $shareableLink = ShareableLink::create([
                'resume_id' => $resumeId,
                'token' => ShareableLink::generateToken(),
                'expires_at' => Carbon::now()->addDays($expiresInDays),
                'is_active' => true,
            ]);

            // Generate frontend URL for the shareable link
            // Try to get from env, fallback to common frontend ports
            $frontendUrl = env('FRONTEND_URL');
            if (!$frontendUrl) {
                // Try to detect from request origin
                $requestOrigin = $request->header('Origin') ?? $request->header('Referer');
                if ($requestOrigin) {
                    $parsed = parse_url($requestOrigin);
                    if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                        $frontendUrl = $parsed['scheme'] . '://' . $parsed['host'] . $port;
                    }
                }
                // Fallback to common frontend dev ports
                $frontendUrl = $frontendUrl ?: 'http://localhost:5173'; // Vite default
            }
            $shareUrl = rtrim($frontendUrl, '/') . "/share/{$shareableLink->token}";

            return response()->json([
                'status' => true,
                'message' => 'Shareable link generated successfully',
                'data' => [
                    'token' => $shareableLink->token,
                    'url' => $shareUrl,
                    'expires_at' => $shareableLink->expires_at->toDateTimeString(),
                    'expires_in_days' => $expiresInDays,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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
            if (!$shareableLink->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This link has expired or been deactivated'
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
            if (!array_key_exists('avatar', $data['basic_info'])) {
                $data['basic_info']['avatar'] = null;
            }
            if (isset($data['basicInfo'])) {
                unset($data['basicInfo']);
            }

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Link not found or invalid',
                'error' => $e->getMessage()
            ], 404);
        }
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
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Deactivate all active links for this resume
            ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return response()->json([
                'status' => true,
                'message' => 'Shareable link deactivated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
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
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $shareableLink = ShareableLink::where('resume_id', $resumeId)
                ->where('is_active', true)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$shareableLink) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active shareable link found',
                    'data' => null
                ], 404);
            }

            // Generate frontend URL for the shareable link
            // Try to get from env, fallback to common frontend ports
            $frontendUrl = env('FRONTEND_URL');
            if (!$frontendUrl) {
                // Try to detect from request origin
                $requestOrigin = $request->header('Origin') ?? $request->header('Referer');
                if ($requestOrigin) {
                    $parsed = parse_url($requestOrigin);
                    if ($parsed && isset($parsed['scheme']) && isset($parsed['host'])) {
                        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                        $frontendUrl = $parsed['scheme'] . '://' . $parsed['host'] . $port;
                    }
                }
                // Fallback to common frontend dev ports
                $frontendUrl = $frontendUrl ?: 'http://localhost:5173'; // Vite default
            }
            $shareUrl = rtrim($frontendUrl, '/') . "/share/{$shareableLink->token}";

            return response()->json([
                'status' => true,
                'message' => 'Shareable link retrieved successfully',
                'data' => [
                    'token' => $shareableLink->token,
                    'url' => $shareUrl,
                    'expires_at' => $shareableLink->expires_at->toDateTimeString(),
                    'is_active' => $shareableLink->is_active,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
