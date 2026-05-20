<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Services\ResumeLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ResumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            $user = auth()->user();

            // Get resumes owned by the user
            $ownedResumes = $user->resumes()->with('template')->get();

            // Get resumes where user is a collaborator (but not the owner)
            $collaboratedResumes = Resume::whereHas('collaborators', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->whereNotNull('accepted_at');
            })->with('template', 'user')
                ->where('user_id', '!=', $user->id) // Exclude resumes where user is also the owner
                ->get();

            // Sort by updated_at descending
            $sortedOwnedResumes = $ownedResumes->sortByDesc('updated_at')->values();
            $sortedSharedResumes = $collaboratedResumes->sortByDesc('updated_at')->values();

            $limits = app(ResumeLimitService::class)->limitsFor($user);

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => [
                    'owned' => $sortedOwnedResumes,
                    'shared' => $sortedSharedResumes,
                ],
                'limits' => $limits,
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        try {

            $validator = Validator::make($request->all(), [
                'template_id' => 'required|exists:templates,id',
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();
            $limitService = app(ResumeLimitService::class);
            if (! $limitService->canCreateOwnedResume($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Free accounts can only create one resume. Upgrade to Pro to create more.',
                    'code' => 'resume_limit_reached',
                ], 403);
            }

            $openToRecruiters = (bool) $user->default_open_to_recruiters;

            $resume = $user->resumes()->create([
                'template_id' => $request->template_id,
                'name' => $request->name,
                'open_to_recruiters' => $openToRecruiters,
                'recruiter_visible_at' => $openToRecruiters ? now() : null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Resume created successfully',
                'data' => $resume,
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

        try {
            $resume = Resume::findOrFail($id)->load('basicInfo', 'experiences.projects', 'educations', 'skills', 'hobbies', 'certificates', 'languages', 'projects', 'template');

            // Check if the authenticated user can edit the resume (owner or collaborator)
            if (! $resume->canBeEditedBy(auth()->id())) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $data = $resume->toArray();
            // Ensure basic_info is always present with avatar (Laravel may use basicInfo or basic_info)
            $basicInfo = $data['basic_info'] ?? $data['basicInfo'] ?? null;
            $data['basic_info'] = is_array($basicInfo) ? $basicInfo : [];
            // Ensure avatar key exists in basic_info for frontend
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
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $resume = Resume::findOrFail($id);

            // Check if the authenticated user can edit the resume (owner or collaborator)
            if (! $resume->canBeEditedBy(auth()->id())) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'section_order' => 'nullable|array',
                'name' => 'sometimes|string|max:255',
                'template_id' => 'sometimes|exists:templates,id',
                'typography' => 'nullable|array',
                'typography.font_family' => 'nullable|string|max:100',
                'typography.font_size' => 'nullable|integer|min:10|max:20',
                'typography.font_id' => 'nullable|integer|exists:pdf_fonts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $resume->update($request->only(['section_order', 'name', 'template_id', 'typography']));

            return response()->json([
                'status' => true,
                'message' => 'Resume updated successfully',
                'data' => $resume,
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
     * Update public profile URL (owner only): /u/{slug}, SEO fields, privacy toggle.
     */
    public function updatePublicProfile(Request $request, string $resume)
    {
        try {
            $resumeModel = Resume::findOrFail($resume);

            if ($resumeModel->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only the resume owner can manage the public profile.',
                ], 403);
            }

            $reserved = [
                'login', 'register', 'admin', 'api', 'share', 'website', 'blog', 'templates',
                'pricing', 'contact', 'faq', 'profile', 'resumes', 'resume', 'auth', 'u', 'p',
                'me', 'stats', 'subscribers', 'collaborate', 'track-request', 'privacy', 'review',
                '403', 'verify', 'cover-letters',
            ];

            $validator = Validator::make($request->all(), [
                'public_profile_enabled' => 'sometimes|boolean',
                'public_profile_slug' => [
                    'nullable',
                    'string',
                    'min:3',
                    'max:40',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    Rule::unique('resumes', 'public_profile_slug')->ignore($resumeModel->id),
                    Rule::unique('shareable_links', 'slug'),
                ],
                'public_profile_meta_title' => 'nullable|string|max:120',
                'public_profile_meta_description' => 'nullable|string|max:320',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $payload = $validator->validated();
            if (array_key_exists('public_profile_slug', $payload) && $payload['public_profile_slug'] !== null) {
                $payload['public_profile_slug'] = strtolower(trim($payload['public_profile_slug']));
                if (in_array($payload['public_profile_slug'], $reserved, true)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This URL is reserved. Please choose another slug.',
                    ], 422);
                }
            }

            $enabled = array_key_exists('public_profile_enabled', $payload)
                ? (bool) $payload['public_profile_enabled']
                : (bool) $resumeModel->public_profile_enabled;

            $slug = array_key_exists('public_profile_slug', $payload)
                ? $payload['public_profile_slug']
                : $resumeModel->public_profile_slug;

            if ($enabled && ! $slug) {
                return response()->json([
                    'status' => false,
                    'message' => 'A public profile slug is required when the public profile is enabled.',
                ], 422);
            }

            if (! $enabled) {
                $payload['public_profile_enabled'] = false;
            }

            if (array_key_exists('public_profile_slug', $payload) && $payload['public_profile_slug'] === '') {
                $payload['public_profile_slug'] = null;
            }

            $resumeModel->fill($payload);
            $resumeModel->save();

            $resumeModel->load('basicInfo', 'template');

            return response()->json([
                'status' => true,
                'message' => 'Public profile updated successfully',
                'data' => $resumeModel,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRecruiterVisibility(Request $request, string $resume)
    {
        try {
            $resumeModel = Resume::findOrFail($resume);

            if ($resumeModel->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only the resume owner can change recruiter visibility.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'open_to_recruiters' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $open = (bool) $request->boolean('open_to_recruiters');
            $resumeModel->open_to_recruiters = $open;
            $resumeModel->recruiter_visible_at = $open ? now() : null;
            $resumeModel->save();

            return response()->json([
                'status' => true,
                'message' => $open
                    ? 'Resume is now visible to approved recruiters.'
                    : 'Resume removed from recruiter talent pool.',
                'data' => $resumeModel->only(['id', 'open_to_recruiters', 'recruiter_visible_at']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
