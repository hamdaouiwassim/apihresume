<?php

namespace App\Http\Controllers;

use App\Models\BasicInfo;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExperienceContoller extends Controller
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
        'company' => 'required|string|max:255',
        'startDate' => 'required|date',
        'endDate' => 'required|date',
        'description' => 'required|string|max:255',
        'position' => 'required|string',

    ]);

    try {
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use updateOrCreate to find a BasicInfo by 'resume_id' and update it,
        // or create a new one if it doesn't exist.
        $basicInfo = Experience::create(

            $request->all() // The attributes to update or create
        );
        return response()->json([
            'status' => true,
            'message' => "Experience added successfully",
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
    public function update(Request $request, Experience $experience)
    {
          //
    $validator = Validator::make($request->all(), [
        'company' => 'required|string|max:255',
        'startDate' => 'required|date',
        'endDate' => 'required|date',
        'description' => 'required|string|max:255',
        'position' => 'required|string',

    ]);

    try {
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use updateOrCreate to find a BasicInfo by 'resume_id' and update it,
        // or create a new one if it doesn't exist.
        $updatedExperience = $experience->update($request->all() // The attributes to update or create
);
        return response()->json([
            'status' => true,
            'message' => "Experience updated successfully",
            'data' => $updatedExperience
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
