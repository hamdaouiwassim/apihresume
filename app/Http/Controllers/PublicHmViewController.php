<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecruiterResumeResource;
use App\Models\RecruiterHmShareLink;
use App\Models\Resume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHmViewController extends Controller
{
    public function show(Request $request, string $token): JsonResponse
    {
        $link = RecruiterHmShareLink::query()
            ->where('token', $token)
            ->first();

        if (! $link) {
            return response()->json(['status' => false, 'message' => 'Link not found.'], 404);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            return response()->json(['status' => false, 'message' => 'This link has expired.'], 410);
        }

        $resume = Resume::with([
            'user:id,name,email,avatar',
            'template',
            'basicInfo',
            'experiences',
            'educations',
            'skills',
            'hobbies',
            'certificates',
            'languages',
            'projects',
        ])->find($link->resume_id);

        if (! $resume) {
            return response()->json(['status' => false, 'message' => 'Resume not found.'], 404);
        }

        $request->merge(['hm_view' => true]);

        return response()->json([
            'status' => true,
            'data' => [
                'label' => $link->label,
                'expires_at' => $link->expires_at,
                'resume' => (new RecruiterResumeResource($resume))->toArray($request),
            ],
        ]);
    }
}
