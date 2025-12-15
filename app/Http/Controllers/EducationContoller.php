<?php

namespace App\Http\Controllers;


use App\Models\Education;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EducationContoller extends Controller
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
        'institution' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'description' => 'required|string|max:5000',
        'degree' => 'required|string',

    ]);

    try {
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can edit the resume and the educations section
        $resume = Resume::findOrFail($request->resume_id);
        $userId = auth()->id();
        
        if (!$resume->canBeEditedBy($userId)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check section-specific permission for collaborators
        if (!$resume->canEditSection($userId, 'educations')) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit the educations section'
            ], 403);
        }

        // Use updateOrCreate to find a BasicInfo by 'resume_id' and update it,
        // or create a new one if it doesn't exist.
        $basicInfo = Education::create(

            $request->all() // The attributes to update or create
        );
        return response()->json([
            'status' => true,
            'message' => "Education added successfully",
            'data' => $basicInfo
        ], 201);

    } catch (\Exception $e) {
        // Log the error for better debugging in a real application
        // \Log::error('Error storing basic info: ' . $e->getMessage());

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
    public function update(Request $request, Education $education)
    {
          //
    $validator = Validator::make($request->all(), [
        'institution' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date',
        'description' => 'required|string|max:5000',
        'degree' => 'required|string',

    ]);

    try {
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can edit the resume and the educations section
        $resume = $education->resume;
        $userId = auth()->id();
        
        if (!$resume || !$resume->canBeEditedBy($userId)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check section-specific permission for collaborators
        if (!$resume->canEditSection($userId, 'educations')) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to edit the educations section'
            ], 403);
        }

        // Use updateOrCreate to find a BasicInfo by 'resume_id' and update it,
        // or create a new one if it doesn't exist.
        $updatedEducation = $education->update($request->all() // The attributes to update or create
);
        return response()->json([
            'status' => true,
            'message' => "Education updated successfully",
            'data' => $updatedEducation
        ], 200);

    } catch (\Exception $e) {
        // Log the error for better debugging in a real application
        // \Log::error('Error storing basic info: ' . $e->getMessage());

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
    public function destroy(string $id)
    {
        //
    }
}
