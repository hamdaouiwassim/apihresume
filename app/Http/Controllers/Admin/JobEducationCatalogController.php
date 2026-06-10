<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobEducationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobEducationCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $items = JobEducationCatalog::query()->ordered()->get();

        return response()->json(['status' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:job_education_catalog,name',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ])->validate();

        $item = JobEducationCatalog::create([
            'name' => trim($validated['name']),
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['status' => true, 'data' => $item], 201);
    }

    public function update(Request $request, JobEducationCatalog $jobEducationCatalog): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:200',
                Rule::unique('job_education_catalog', 'name')->ignore($jobEducationCatalog->id),
            ],
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ])->validate();

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }

        $jobEducationCatalog->update($validated);

        return response()->json(['status' => true, 'data' => $jobEducationCatalog->fresh()]);
    }

    public function destroy(JobEducationCatalog $jobEducationCatalog): JsonResponse
    {
        $jobEducationCatalog->delete();

        return response()->json(['status' => true, 'message' => 'Education option removed from catalog']);
    }
}
