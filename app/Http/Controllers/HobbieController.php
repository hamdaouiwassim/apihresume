<?php

namespace App\Http\Controllers;

use App\Models\Hobbie;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HobbieController extends Controller
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
        //
        $validator = Validator::make($request->all(), [
            'resume_id' => 'required|exists:resumes,id',
            'name' => 'required|string|max:255',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the hobbies section
            $resume = Resume::findOrFail($request->resume_id);
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'hobbies')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the hobbies section'
                ], 403);
            }

            $hobbie = Hobbie::create($request->all());

            return response()->json([
                'status' => true,
                'message' => "Hobby added successfully",
                'data' => $hobbie
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        try {
            $hobbie = Hobbie::findOrFail($id);

            $resume = $hobbie->resume;
            
            if (!$resume) {
                return response()->json([
                    'status' => false,
                    'message' => 'Associated resume not found'
                ], 404);
            }

            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Hobby fetched successfully',
                'data' => $hobbie
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, Hobbie $hobbie)
    {
        //
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
return response()->json([
    'status' => true,
    'message' => 'Hobby fetched successfully',
    'data' => $hobbie
], 200);
            //$resume = $hobbie->resume;
            $resume = Resume::findOrFail($hobbie->resume_id);
            if (!$resume) {
                return response()->json([
                    'status' => false,
                    'message' => 'Associated resume not found'
                ], 404);
            }

            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'hobbies')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the hobbies section'
                ], 403);
            }

            $hobbie->update($request->only(['name']));

            return response()->json([
                'status' => true,
                'message' => "Hobby updated successfully",
                'data' => $hobbie->fresh()
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
    public function destroy(Hobbie $hobbie)
    {
        //
        try {
            // Check if user can edit the resume and the hobbies section
            $resume = $hobbie->resume;
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'hobbies')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the hobbies section'
                ], 403);
            }

            $hobbie->delete();

            return response()->json([
                'status' => true,
                'message' => "Hobby deleted successfully"
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

