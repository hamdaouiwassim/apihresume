<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Resume;
class ResumeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try{
             // Assuming User model has a 'resumes' relationship
            $resumes = auth()->user()->resumes()->with('template')->orderBy('updated_at', 'desc')->get();
            return response()->json([
                "status" => true,
                "message" => "Resume fetched successfully",
                "data" => $resumes
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                "status" => false,
                "message" => "Something went wrong",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //

        try {


             $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:templates,id',
                'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "message" => "Validation error",
                "errors" => $validator->errors()
            ], 422);
        }


            $resume = auth()->user()->resumes()->create([
                'template_id' => $request->template_id,
                'name' => $request->name,
            ]);

            return response()->json([
                "status" => true,
                "message" => "Resume created successfully",
                "data" => $resume
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Something went wrong",
                "error" => $e->getMessage()
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
            $resume = Resume::findOrFail($id)->load('basicInfo',"experiences","educations","skills","hobbies","certificates");

            // Check if the authenticated user owns the resume
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    "status" => false,
                    "message" => "Unauthorized access"
                ], 403);
            }

            return response()->json([
                "status" => true,
                "message" => "Resume fetched successfully",
                "data" => $resume
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Something went wrong",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
