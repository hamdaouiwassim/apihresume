<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Support\AdminPagination;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    /**
     * List all resumes with optional filters
     */
    public function index(Request $request)
    {
        try {
            $perPage = AdminPagination::resolve($request);
            $search = $request->input('search');
            $templateId = $request->input('template_id');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $userId = $request->input('user_id');
            $trashed = $request->input('trashed');

            $query = match ($trashed) {
                'only' => Resume::onlyTrashed(),
                'with' => Resume::withTrashed(),
                default => Resume::query(),
            };

            $query = $query->with(['user' => fn ($q) => $q->withTrashed(), 'template:id,name'])
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

            if ($userId) {
                $query->where('user_id', $userId);
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
                'data' => $resumes,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch resumes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single resume with its relationships
     */
    public function show($id)
    {
        try {
            $resume = Resume::withTrashed()
                ->with(['user' => fn ($q) => $q->withTrashed(), 'template', 'basicInfo', 'experiences', 'educations', 'skills', 'certificates', 'hobbies', 'languages', 'projects'])
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Resume fetched successfully',
                'data' => $resume,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Resume not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Delete a resume (admin).
     */
    public function destroy($id)
    {
        try {
            $resume = Resume::findOrFail($id);
            $resume->delete();

            return response()->json([
                'status' => true,
                'message' => 'Resume moved to trash. Admins can restore it anytime.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete resume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $resume = Resume::onlyTrashed()->findOrFail($id);
            $resume->restore();

            return response()->json([
                'status' => true,
                'message' => 'Resume restored successfully.',
                'data' => $resume->fresh()->load(['user', 'template']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to restore resume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function forceDestroy($id)
    {
        try {
            $resume = Resume::withTrashed()->findOrFail($id);
            $resume->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'Resume permanently deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to permanently delete resume',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
