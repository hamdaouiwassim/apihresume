<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    /**
     * List all resumes with optional filters
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $templateId = $request->input('template_id');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = Resume::with(['user:id,name,email,avatar', 'template:id,name'])
                ->orderByDesc('updated_at');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if ($templateId) {
                $query->where('template_id', $templateId);
            }

            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $resumes = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Resumes fetched successfully',
                'data' => $resumes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch resumes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a single resume with its relationships
     */
    public function show($id)
    {
        try {
            $resume = Resume::with([
                'user:id,name,email,avatar',
                'template',
                'basicInfo',
                'experiences',
                'educations',
                'skills',
                'hobbies',
                'certificates',
            ])->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => $resume
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Resume not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

