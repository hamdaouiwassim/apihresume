<?php

namespace App\Http\Controllers;

use App\Models\WorkCertificate;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WorkCertificateController extends Controller
{
    public function index()
    {
        $items = Auth::user()->workCertificates()->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $certificate = Auth::user()->workCertificates()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Work certificate saved successfully',
            'data' => $certificate,
        ], 201);
    }

    public function show(WorkCertificate $work_certificate)
    {
        $this->authorizeOwner($work_certificate);

        return response()->json([
            'status' => true,
            'data' => $work_certificate,
        ]);
    }

    public function update(Request $request, WorkCertificate $work_certificate)
    {
        $this->authorizeOwner($work_certificate);

        $validated = $this->validatePayload($request);
        $work_certificate->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Work certificate updated successfully',
            'data' => $work_certificate->fresh(),
        ]);
    }

    public function destroy(WorkCertificate $work_certificate)
    {
        $this->authorizeOwner($work_certificate);
        $work_certificate->delete();

        return response()->json([
            'status' => true,
            'message' => 'Work certificate deleted successfully',
        ]);
    }

    public function generatePdf(Request $request, WorkCertificate $work_certificate)
    {
        $this->authorizeOwner($work_certificate);

        $requested = $request->query('locale');
        $locale = in_array($requested, ['en', 'fr'], true)
            ? $requested
            : (in_array($work_certificate->locale, ['en', 'fr'], true) ? $work_certificate->locale : 'en');
        $strings = $this->pdfStrings($locale);

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        $html = view('pdf.work_certificate', [
            'c' => $work_certificate,
            'strings' => $strings,
            'locale' => $locale,
            'startFmt' => $this->formatDate($work_certificate->employment_start, $locale),
            'endFmt' => $work_certificate->is_current_employment
                ? null
                : ($work_certificate->employment_end ? $this->formatDate($work_certificate->employment_end, $locale) : null),
            'letterDateFmt' => $work_certificate->letter_date
                ? $this->formatDate($work_certificate->letter_date, $locale)
                : $this->formatDate(Carbon::now(), $locale),
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = str($work_certificate->title ?: 'work-certificate')->slug().'.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    private function formatDate(mixed $date, string $locale): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $c = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $c->locale($locale === 'fr' ? 'fr' : 'en');

        return $c->translatedFormat('j F Y');
    }

    /**
     * @return array<string, string>
     */
    private function pdfStrings(string $locale): array
    {
        $en = [
            'heading' => 'Certificate of employment',
            'to_whom' => 'To whom it may concern,',
            'body_intro' => 'This is to certify that',
            'was_employed' => 'was employed at',
            'as' => 'in the position of',
            'from' => 'from',
            'to' => 'to',
            'present' => 'present',
            'duties' => 'Main responsibilities and duties included:',
            'closing' => 'This certificate is issued upon the employee\'s request for whatever legal purpose it may serve.',
            'signature_line' => 'Authorized signature',
        ];

        $fr = [
            'heading' => 'Attestation de travail',
            'to_whom' => 'À qui de droit,',
            'body_intro' => 'Nous certifions que',
            'was_employed' => 'a été employé(e) au sein de',
            'as' => 'en qualité de',
            'from' => 'du',
            'to' => 'au',
            'present' => 'à ce jour',
            'duties' => 'Principales responsabilités et missions :',
            'closing' => 'La présente attestation est délivrée à la demande de l\'intéressé(e) pour servir et valoir ce que de droit.',
            'signature_line' => 'Signature et cachet de l\'employeur',
        ];

        return $locale === 'fr' ? $fr : $en;
    }

    private function validatePayload(Request $request): array
    {
        if ($request->boolean('is_current_employment')) {
            $request->merge(['employment_end' => null]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'employee_name' => 'required|string|max:255',
            'employee_job_title' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:5000',
            'employment_start' => 'required|date',
            'employment_end' => [
                'nullable',
                'date',
                'after_or_equal:employment_start',
                Rule::requiredIf(! $request->boolean('is_current_employment')),
            ],
            'is_current_employment' => 'boolean',
            'duties_summary' => 'nullable|string|max:10000',
            'letter_place' => 'nullable|string|max:255',
            'letter_date' => 'nullable|date',
            'signer_name_title' => 'nullable|string|max:255',
            'locale' => ['nullable', Rule::in(['en', 'fr'])],
        ]);

        $validated['locale'] = $validated['locale'] ?? 'en';
        $validated['is_current_employment'] = $request->boolean('is_current_employment');

        return $validated;
    }

    private function authorizeOwner(WorkCertificate $work_certificate): void
    {
        if ($work_certificate->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
