<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|exists:resumes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'technologies' => 'nullable|string|max:500',
            'url' => 'nullable|url|max:255',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the projects section
            $resume = Resume::findOrFail($request->resume_id);
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'projects')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the projects section'
                ], 403);
            }

            $project = Project::create($request->all());
            return response()->json([
                'status' => true,
                'message' => "Project added successfully",
                'data' => $project
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'technologies' => 'nullable|string|max:500',
            'url' => 'nullable|url|max:255',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the projects section
            $resume = $project->resume;
            $userId = auth()->id();
            
            if (!$resume || !$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'projects')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the projects section'
                ], 403);
            }

            $project->update($request->all());
            return response()->json([
                'status' => true,
                'message' => "Project updated successfully",
                'data' => $project
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
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        try {
            $resume = $project->resume;
            $userId = auth()->id();
            
            if (!$resume || !$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'projects')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the projects section'
                ], 403);
            }

            $project->delete();
            return response()->json([
                'status' => true,
                'message' => "Project deleted successfully"
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
