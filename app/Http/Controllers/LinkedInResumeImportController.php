<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Services\LinkedInResumeImportService;
use App\Support\ApiJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinkedInResumeImportController extends Controller
{
    public function __invoke(Request $request, Resume $resume, LinkedInResumeImportService $importService)
    {
        try {
            $result = $importService->import($request->user(), $resume);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('LinkedIn resume import failed', [
                'resume_id' => $resume->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(array_merge([
                'status' => false,
                'message' => 'Something went wrong while importing from LinkedIn.',
            ], ApiJson::debugError($e)), 500);
        }

        $msg = 'Imported from LinkedIn.';
        if (($result['experiences_added'] ?? 0) > 0) {
            $msg .= ' Added '.$result['experiences_added'].' experience row(s) from your headline.';
        }

        return response()->json([
            'status' => true,
            'message' => $msg,
            'import' => $result,
        ]);
    }
}
