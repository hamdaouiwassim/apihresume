<?php

namespace App\Http\Controllers;

use App\Models\CoverLetterTemplate;
use Illuminate\Http\Request;

class CoverLetterTemplateController extends Controller
{
    /**
     * Display a listing of the active resources.
     */
    public function index(Request $request)
    {
        $query = CoverLetterTemplate::where('is_active', true);

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        $templates = $query->get();

        return response()->json([
            'status' => true,
            'data' => $templates
        ]);
    }
}
