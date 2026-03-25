<?php

namespace App\Http\Controllers;

use App\Models\CoverLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Dompdf\Dompdf;
use Dompdf\Options;

class CoverLetterController extends Controller
{
    public function index()
    {
        $coverLetters = Auth::user()->coverLetters()->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $coverLetters
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_company' => 'nullable|string|max:255',
            'recipient_address' => 'nullable|string',
            'recipient_email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'date' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'style' => 'nullable|string|max:255',
        ]);

        $coverLetter = Auth::user()->coverLetters()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Cover letter created successfully',
            'data' => $coverLetter
        ], 201);
    }

    public function show(CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $coverLetter
        ]);
    }

    public function update(Request $request, CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_company' => 'nullable|string|max:255',
            'recipient_address' => 'nullable|string',
            'recipient_email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'date' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'style' => 'nullable|string|max:255',
        ]);

        $coverLetter->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Cover letter updated successfully',
            'data' => $coverLetter
        ]);
    }

    public function destroy(CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $coverLetter->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cover letter deleted successfully'
        ]);
    }

    public function generatePDF(CoverLetter $coverLetter)
    {
        if ($coverLetter->user_id !== Auth::id()) {
            abort(403);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        $html = view('pdf.cover_letter', [
            'coverLetter' => $coverLetter,
            'user' => Auth::user(),
            'basicInfo' => Auth::user()->resumes()->orderBy('updated_at', 'desc')->first()?->basicInfo
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . str($coverLetter->title)->slug() . '.pdf"');
    }
}
