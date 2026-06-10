<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobSkillCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JobSkillCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $items = JobSkillCatalog::query()->ordered()->get();

        return response()->json(['status' => true, 'data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:120|unique:job_skill_catalog,name',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ])->validate();

        $item = JobSkillCatalog::create([
            'name' => trim($validated['name']),
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['status' => true, 'data' => $item], 201);
    }

    public function update(Request $request, JobSkillCatalog $jobSkillCatalog): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:120',
                Rule::unique('job_skill_catalog', 'name')->ignore($jobSkillCatalog->id),
            ],
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
        ])->validate();

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }

        $jobSkillCatalog->update($validated);

        return response()->json(['status' => true, 'data' => $jobSkillCatalog->fresh()]);
    }

    public function destroy(JobSkillCatalog $jobSkillCatalog): JsonResponse
    {
        $jobSkillCatalog->delete();

        return response()->json(['status' => true, 'message' => 'Skill removed from catalog']);
    }
}
