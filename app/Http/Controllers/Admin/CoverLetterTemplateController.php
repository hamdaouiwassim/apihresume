<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoverLetterTemplate;
use Illuminate\Http\Request;

class CoverLetterTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CoverLetterTemplate::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        $templates = $query->latest()->paginate($request->input('per_page', 10));

        return response()->json([
            'status' => true,
            'data' => $templates
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'job_type' => 'required|string|max:255',
            'language' => 'required|string|size:2',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $template = CoverLetterTemplate::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Template created successfully',
            'data' => $template
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CoverLetterTemplate $coverLetterTemplate)
    {
        return response()->json([
            'status' => true,
            'data' => $coverLetterTemplate
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $template = CoverLetterTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'job_type' => 'sometimes|required|string|max:255',
            'language' => 'sometimes|required|string|size:2',
            'subject' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'is_active' => 'boolean'
        ]);

        $template->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Template updated successfully',
            'data' => $template
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $template = CoverLetterTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'status' => true,
            'message' => 'Template deleted successfully'
        ]);
    }
}
