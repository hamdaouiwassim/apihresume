<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\RecruiterJob;
use App\Services\RecruiterResumeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function update(Request $request, int $id): JsonResponse
    {
        $application = JobApplication::query()
            ->whereHas('job', fn ($q) => $q->where('created_by_user_id', $request->user()->id))
            ->findOrFail($id);

        $validated = Validator::make($request->all(), [
            'status' => 'sometimes|in:new,reviewing,shortlisted,rejected,hired',
            'internal_notes' => 'nullable|string|max:10000',
            'match_score' => 'nullable|integer|min:0|max:100',
        ])->validate();

        $application->update($validated);

        if (($validated['status'] ?? null) === 'shortlisted') {
            app(RecruiterResumeAccessService::class)->grantAccess(
                $application->resume_id,
                $request->user()->id,
                $request->user()->id,
                'application',
            );
        }

        return response()->json(['status' => true, 'data' => $application->fresh(['user', 'resume'])]);
    }
}
