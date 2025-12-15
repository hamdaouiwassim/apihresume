<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
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
            'language' => 'required|string|max:255',
            'proficiency' => 'required|string|in:Native,Fluent,Intermediate,Basic',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the languages section
            $resume = Resume::findOrFail($request->resume_id);
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'languages')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the languages section'
                ], 403);
            }

            $language = Language::create($request->all());
            return response()->json([
                'status' => true,
                'message' => "Language added successfully",
                'data' => $language
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:255',
            'proficiency' => 'required|string|in:Native,Fluent,Intermediate,Basic',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the languages section
            $resume = $language->resume;
            $userId = auth()->id();
            
            if (!$resume || !$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'languages')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the languages section'
                ], 403);
            }

            $language->update($request->all());
            return response()->json([
                'status' => true,
                'message' => "Language updated successfully",
                'data' => $language
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
    public function destroy(Language $language)
    {
        try {
            // Check if user can edit the resume and the languages section
            $resume = $language->resume;
            $userId = auth()->id();
            
            if (!$resume || !$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'languages')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the languages section'
                ], 403);
            }

            $language->delete();
            return response()->json([
                'status' => true,
                'message' => "Language deleted successfully"
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
