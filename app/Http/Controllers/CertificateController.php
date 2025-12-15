<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
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
            'issuer' => 'nullable|string|max:255',
            'date_obtained' => 'nullable|date',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the certificates section
            $resume = Resume::findOrFail($request->resume_id);
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'certificates')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the certificates section'
                ], 403);
            }

            $certificate = Certificate::create($request->all());

            return response()->json([
                'status' => true,
                'message' => "Certificate added successfully",
                'data' => $certificate
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
            $certificate = Certificate::findOrFail($id);

            // Check if the authenticated user owns the resume
            $resume = $certificate->resume;
            if ($resume->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Certificate fetched successfully',
                'data' => $certificate
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
    public function update(Request $request, Certificate $certificate)
    {
        //
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'issuer' => 'nullable|string|max:255',
            'date_obtained' => 'nullable|date',
        ]);

        try {
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user can edit the resume and the certificates section
            $resume = $certificate->resume;
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'certificates')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the certificates section'
                ], 403);
            }

            $certificate->update($request->only(['name', 'issuer', 'date_obtained']));

            return response()->json([
                'status' => true,
                'message' => "Certificate updated successfully",
                'data' => $certificate->fresh()
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
    public function destroy(Certificate $certificate)
    {
        //
        try {
            // Check if user can edit the resume and the certificates section
            $resume = $certificate->resume;
            $userId = auth()->id();
            
            if (!$resume->canBeEditedBy($userId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Check section-specific permission for collaborators
            if (!$resume->canEditSection($userId, 'certificates')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to edit the certificates section'
                ], 403);
            }

            $certificate->delete();

            return response()->json([
                'status' => true,
                'message' => "Certificate deleted successfully"
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

