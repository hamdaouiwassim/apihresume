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
            $user = auth()->user();
            
            // Get resumes owned by the user
            $ownedResumes = $user->resumes()->with('template')->get();
            
            // Get resumes where user is a collaborator (but not the owner)
            $collaboratedResumes = Resume::whereHas('collaborators', function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->whereNotNull('accepted_at');
            })->with('template', 'user')
            ->where('user_id', '!=', $user->id) // Exclude resumes where user is also the owner
            ->get();
            
            // Sort by updated_at descending
            $sortedOwnedResumes = $ownedResumes->sortByDesc('updated_at')->values();
            $sortedSharedResumes = $collaboratedResumes->sortByDesc('updated_at')->values();
            
            return response()->json([
                "status" => true,
                "message" => "Resume fetched successfully",
                "data" => [
                    "owned" => $sortedOwnedResumes,
                    "shared" => $sortedSharedResumes
                ]
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
            $resume = Resume::findOrFail($id)->load('basicInfo',"experiences","educations","skills","hobbies","certificates","languages","template");

            // Check if the authenticated user can edit the resume (owner or collaborator)
            if (!$resume->canBeEditedBy(auth()->id())) {
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
