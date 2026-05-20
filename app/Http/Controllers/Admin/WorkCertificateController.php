<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkCertificate;
use App\Support\AdminPagination;
use Illuminate\Http\Request;

class WorkCertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkCertificate::with('user:id,name,email')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('employee_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $certificates = $query->paginate(AdminPagination::resolve($request));

        return response()->json([
            'status' => true,
            'data' => $certificates,
        ]);
    }

    public function show(string $id)
    {
        $certificate = WorkCertificate::with('user:id,name,email')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $certificate,
        ]);
    }

    public function destroy(string $id)
    {
        $certificate = WorkCertificate::findOrFail($id);
        $certificate->delete();

        return response()->json([
            'status' => true,
            'message' => 'Work certificate deleted successfully',
        ]);
    }
}
