<?php

namespace App\Http\Controllers;

use App\Models\JobEducationCatalog;
use App\Models\JobSkillCatalog;
use Illuminate\Http\JsonResponse;

class JobCatalogController extends Controller
{
    public function skills(): JsonResponse
    {
        $items = JobSkillCatalog::query()->active()->ordered()->get(['id', 'name']);

        return response()->json(['status' => true, 'data' => $items]);
    }

    public function education(): JsonResponse
    {
        $items = JobEducationCatalog::query()->active()->ordered()->get(['id', 'name']);

        return response()->json(['status' => true, 'data' => $items]);
    }
}
