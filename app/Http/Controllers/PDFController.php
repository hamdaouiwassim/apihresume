<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Browsershot\Browsershot;

class PDFController extends Controller
{
    /**
     * Generate PDF from HTML content
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'html' => 'required|string',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $html = $request->input('html');
            $filename = $request->input('filename', 'resume.pdf');

            // Generate PDF using Browsershot (Puppeteer)
            $pdf = Browsershot::html($html)
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu'
                ]) // For server environments
                ->margins(20, 20, 20, 20, 'mm') // Top, Right, Bottom, Left (matches p-8 padding)
                ->format('A4')
                ->showBackground()
                ->waitUntilNetworkIdle(false) // Wait until network is idle
                ->dismissDialogs()
                ->timeout(60) // 60 second timeout
                ->pdf();

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'PDF generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
