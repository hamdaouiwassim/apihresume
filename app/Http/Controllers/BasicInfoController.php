<?php

namespace App\Http\Controllers;

use App\Models\BasicInfo;
use App\Models\Resume;
use App\Support\ApiJson;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BasicInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|exists:resumes,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'job_title' => 'required|string|max:255',
            'professional_summary' => 'required|string|max:5000',
            'location' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|max:255',
            'github' => 'nullable|url|max:255',
            'website' => 'nullable|url|max:255',
            'avatar' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $resume = Resume::findOrFail($data['resume_id']);
            $userId = auth()->id();

            if (! $resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            if (! $resume->canEditSection($userId, 'basic_info')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the basic info section',
                ], 403);
            }

            $attributes = Arr::only($data, [
                'full_name',
                'email',
                'phone',
                'job_title',
                'professional_summary',
                'location',
                'linkedin',
                'github',
                'website',
                'avatar',
            ]);

            $basicInfo = BasicInfo::updateOrCreate(
                ['resume_id' => $data['resume_id']],
                $attributes
            );

            $isNewRecord = $basicInfo->wasRecentlyCreated;
            $message = $isNewRecord ? 'Basic info added successfully' : 'Basic info updated successfully';
            $statusCode = $isNewRecord ? 201 : 200;

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $basicInfo,
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('Error storing basic info: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(array_merge([
                'status' => false,
                'message' => 'Something went wrong',
            ], ApiJson::debugError($e)), 500);
        }
    }

    /**
     * Upload avatar for resume basic info.
     */
    public function uploadAvatar(Request $request, string $resumeId)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resume = Resume::findOrFail($resumeId);
        $userId = auth()->id();

        if (!$resume->canBeEditedBy($userId)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (!$resume->canEditSection($userId, 'basic_info')) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit the basic info section',
            ], 403);
        }

        try {
            $file = $request->file('avatar');

            $dir = 'resume-avatars/' . $resumeId;
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir, 0755, true);
            }

            $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $path = $file->storeAs($dir, $filename, 'public');

            if (!$path || !Storage::disk('public')->exists($path)) {
                throw new \Exception('Failed to store avatar file.');
            }

            $scheme = $request->getScheme();
            $host = $request->getHost();
            $port = $request->getPort();
            $baseUrl = $scheme . '://' . $host . ($port && $port != 80 && $port != 443 ? ':' . $port : '');
            $avatarUrl = $baseUrl . '/storage/' . $path;

            // Persist avatar to basic_info so it's saved with resume data (no extra "Save" needed)
            $basicInfo = BasicInfo::where('resume_id', $resumeId)->first();
            if ($basicInfo) {
                $basicInfo->avatar = $avatarUrl;
                $basicInfo->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $avatarUrl,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Avatar upload failed: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(array_merge([
                'status' => false,
                'message' => 'Failed to upload avatar',
            ], ApiJson::debugError($e)), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
