<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use Illuminate\Http\Request;

class CoverLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = CoverLetter::with('user:id,name,email')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_company', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $letters = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'status' => true,
            'data' => $letters,
        ]);
    }

    public function show(string $id)
    {
        $letter = CoverLetter::with('user:id,name,email')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $letter,
        ]);
    }

    public function destroy(string $id)
    {
        $letter = CoverLetter::findOrFail($id);
        $letter->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cover letter deleted successfully',
        ]);
    }
}

