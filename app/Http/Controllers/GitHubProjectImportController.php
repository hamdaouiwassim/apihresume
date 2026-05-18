<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use App\Models\Resume;
use App\Services\GitHubRepoMetadataService;
use App\Support\ApiJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubProjectImportController extends Controller
{
    public function __invoke(Request $request, Resume $resume, GitHubRepoMetadataService $github)
    {
        $validated = $request->validate([
            'repo_url' => ['required', 'string', 'max:2048'],
            'experience_id' => ['nullable', 'integer', 'exists:experiences,id'],
        ]);

        $userId = (int) $request->user()->id;

        if (! $resume->canBeEditedBy($userId)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (! $resume->canEditSection($userId, 'projects')) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit the projects section',
            ], 403);
        }

        $experienceId = $validated['experience_id'] ?? null;
        if ($experienceId !== null) {
            $experience = Experience::where('id', $experienceId)->where('resume_id', $resume->id)->first();
            if (! $experience) {
                return response()->json([
                    'status' => false,
                    'message' => 'Experience not found or does not belong to this resume',
                ], 422);
            }
        }

        try {
            ['owner' => $owner, 'repo' => $repo] = $github->parseOwnerAndRepo($validated['repo_url']);
            $userToken = filled($request->user()->github_import_token)
                ? (string) $request->user()->github_import_token
                : null;
            $draft = $github->buildProjectDraftFromRepo($owner, $repo, $userToken);
            $draft['experience_id'] = $experienceId;

            return response()->json([
                'status' => true,
                'data' => $draft,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('GitHub project preview failed', [
                'resume_id' => $resume->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(array_merge([
                'status' => false,
                'message' => 'Something went wrong while loading the repository.',
            ], ApiJson::debugError($e)), 500);
        }
    }
}
