<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\TemplateProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemplateProposalController extends Controller
{
    /**
     * List the recruiter's template proposals
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);

            $proposals = TemplateProposal::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Template proposals fetched successfully',
                'data' => $proposals
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch template proposals',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new template proposal
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'preview_image_url' => 'nullable|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proposal = TemplateProposal::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'preview_image_url' => $request->preview_image_url,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Template proposal submitted successfully',
                'data' => $proposal
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to submit proposal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

