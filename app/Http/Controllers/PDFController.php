<?php

namespace App\Http\Controllers;

use App\Models\PdfFont;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PDFController extends Controller
{
    /**
     * Generate PDF from HTML content
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => 'nullable|array',
            'resume.experience' => 'nullable|array',
            'resume.education' => 'nullable|array',
            'resume.skills' => 'nullable|array',
            'resume.certifications' => 'nullable|array',
            'resume.languages' => 'nullable|array',
            'html' => 'nullable|string',
            'filename' => 'nullable|string|max:255',
            'locale' => 'nullable|in:en,fr',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $locale = $request->input('locale', 'en');
        if (!in_array($locale, ['en', 'fr'], true)) {
            $locale = 'en';
        }
        $resume = $this->normalizeResumeInput($request->input('resume'));
        $htmlInput = $request->input('html');

        if (!$resume && empty($htmlInput)) {
            return response()->json([
                'status' => false,
                'message' => 'Either resume data or raw HTML must be provided.',
            ], 422);
        }

        try {
            $filename = $request->input('filename', 'resume.pdf');

            // Use writable storage for Dompdf fonts (vendor/lib/fonts may not be writable)
            $fontDir = storage_path('app/dompdf-fonts');
            if (!is_dir($fontDir)) {
                mkdir($fontDir, 0755, true);
            }

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('dpi', 120);
            $options->set('fontDir', $fontDir);
            $options->set('fontCache', $fontDir);

            $dompdf = new Dompdf($options);

            // Register custom uploaded fonts before loading HTML
            $this->registerCustomFonts($dompdf);

            $dompdf->setPaper('A4');
            $dompdf->loadHtml(
                $resume
                    ? $this->renderResumeTemplate($resume, $locale)
                    : $this->wrapRawHtml($htmlInput)
            );
            $dompdf->render();

            $pdf = $dompdf->output();

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

    /**
     * Preview the resume template in the browser.
     */
    public function preview(Request $request)
    {
        $locale = $request->input('locale', 'en');
        if (!in_array($locale, ['en', 'fr'], true)) {
            $locale = 'en';
        }
        $resume = $this->normalizeResumeInput($request->input('resume')) ?: $this->sampleResumeData();

        return view('pdf.resume', [
            'resume' => $resume,
            'strings' => $this->getPdfTranslations($locale),
            'template_view' => $this->resolveTemplateView($resume),
        ]);
    }

    private function renderResumeTemplate(array $resume, string $locale = 'en'): string
    {
        return view('pdf.resume', [
            'resume' => $resume,
            'strings' => $this->getPdfTranslations($locale),
            'template_view' => $this->resolveTemplateView($resume),
        ])->render();
    }

    private function getPdfTranslations(string $locale): array
    {
        $defaults = [
            'lang' => 'en',
            'professional_summary' => 'Professional Summary',
            'work_experience' => 'Work Experience',
            'education' => 'Education',
            'skills' => 'Skills',
            'interests' => 'Interests',
            'certifications' => 'Certifications',
            'languages' => 'Languages',
            'graduated' => 'Graduated',
            'contact' => 'Contact',
            'present' => 'Present',
            'hobbies' => 'Hobbies',
            'projects' => 'Projects',
        ];

        $map = [
            'fr' => [
                'lang' => 'fr',
                'professional_summary' => 'Résumé professionnel',
                'work_experience' => 'Expérience professionnelle',
                'education' => 'Formation',
                'skills' => 'Compétences',
                'interests' => 'Centres d\'intérêt',
                'certifications' => 'Certifications',
                'languages' => 'Langues',
                'graduated' => 'Diplômé',
                'contact' => 'Contact',
                'present' => 'Présent',
                'hobbies' => 'Passions',
                'projects' => 'Projets',
            ],
        ];

        return $map[$locale] ?? $defaults;
    }

    private function resolveTemplateView(array $resume): string
    {
        $layout = strtolower($resume['template_layout'] ?? 'classic');

        return match ($layout) {
            'executive-split', 'executive_split', 'executive' => 'pdf.templates.executive-split',
            'modern-professional', 'modern_professional', 'modern', 'professional' => 'pdf.templates.modern-professional',
            default => 'pdf.templates.classic',
        };
    }

    private function wrapRawHtml(?string $html): string
    {
        $html = $html ?: '';

        if (str_contains($html, '<html')) {
            return $html;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20mm; }
        body { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; margin: 0; padding: 20px; }
    </style>
</head>
<body>
{$html}
</body>
</html>
HTML;
    }

    private function normalizeResumeInput($input): ?array
    {
        if (is_array($input)) {
            return $input;
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Register all active custom fonts with the Dompdf instance.
     */
    private function registerCustomFonts(Dompdf $dompdf): void
    {
        try {
            $fonts = PdfFont::active()->get();

            if ($fonts->isEmpty()) {
                return;
            }

            $fontMetrics = $dompdf->getFontMetrics();

            foreach ($fonts as $font) {
                $familyLower = strtolower($font->family_name);

                // Dompdf expects a URI (file:// for local files). Use explicit file:// prefix for reliable loading.
                $toUri = function (string $path) {
                    $path = str_replace('\\', '/', $path);
                    return 'file://' . (str_starts_with($path, '/') ? '' : '/') . $path;
                };

                // Register regular (required) - files are on 'local' disk (storage/app/private)
                if ($font->regular_path) {
                    $fullPath = Storage::disk('local')->path($font->regular_path);
                    if (!file_exists($fullPath)) {
                        Log::warning("PDF font file not found: {$fullPath} (regular_path: {$font->regular_path})");
                    } else {
                        $ok = $fontMetrics->registerFont(
                            ['family' => $familyLower, 'style' => 'normal', 'weight' => 'normal'],
                            $toUri($fullPath)
                        );
                        if (!$ok) {
                            Log::warning("Dompdf registerFont failed for {$familyLower} (regular)");
                        }
                    }
                }

                // Register bold (optional, falls back to regular)
                if ($font->bold_path) {
                    $fullPath = Storage::disk('local')->path($font->bold_path);
                    if (file_exists($fullPath)) {
                        $fontMetrics->registerFont(
                            ['family' => $familyLower, 'style' => 'normal', 'weight' => 'bold'],
                            $toUri($fullPath)
                        );
                    }
                }

                // Register italic (optional)
                if ($font->italic_path) {
                    $fullPath = Storage::disk('local')->path($font->italic_path);
                    if (file_exists($fullPath)) {
                        $fontMetrics->registerFont(
                            ['family' => $familyLower, 'style' => 'italic', 'weight' => 'normal'],
                            $toUri($fullPath)
                        );
                    }
                }

                // Register bold italic (optional)
                if ($font->bold_italic_path) {
                    $fullPath = Storage::disk('local')->path($font->bold_italic_path);
                    if (file_exists($fullPath)) {
                        $fontMetrics->registerFont(
                            ['family' => $familyLower, 'style' => 'italic', 'weight' => 'bold'],
                            $toUri($fullPath)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to register custom fonts: ' . $e->getMessage());
        }
    }

    private function sampleResumeData(): array
    {
        return [
            'name' => 'Michael Harris',
            'tagline' => 'Digital Marketing | SEO | SEM | Content Marketing',
            'contact' => [
                'location' => 'Sydney, Australia',
                'email' => 'michael.harris@email.com',
                'phone' => '+61 412 345 678',
                'linkedin' => 'linkedin.com/in/michaelharris',
            ],
            'summary' => 'Results-oriented marketing professional with over 5 years of experience in digital marketing, brand strategy, and content creation. Proven ability to drive brand growth, increase online engagement, and deliver data-driven results.',
            'experience' => [
                [
                    'title' => 'Marketing Manager',
                    'company' => 'XYZ Corporation',
                    'location' => 'Sydney, NSW',
                    'start' => 'January 2022',
                    'end' => 'Present',
                    'bullets' => [
                        'Lead a team of 5 in creating and executing digital marketing strategies across multiple platforms.',
                        'Achieved a 35% increase in website traffic and 50% boost in social media engagement within the first year.',
                        'Managed a marketing budget of $200,000, ensuring maximum ROI through cost-effective advertising strategies.',
                    ],
                ],
                [
                    'title' => 'Digital Marketing Specialist',
                    'company' => 'ABC Solutions',
                    'location' => 'Melbourne, VIC',
                    'start' => 'June 2018',
                    'end' => 'December 2021',
                    'bullets' => [
                        'Developed and executed SEO and SEM strategies that increased organic search traffic by 25%.',
                        'Created and managed Google Ads and Facebook Ads campaigns, resulting in a 20% increase in qualified leads.',
                        'Produced engaging content for blogs, newsletters, and social media platforms to attract target audiences.',
                    ],
                ],
            ],
            'education' => [
                [
                    'degree' => 'Bachelor of Marketing',
                    'school' => 'University of Sydney',
                    'location' => 'Sydney, NSW',
                    'graduated' => '2018',
                ],
            ],
            'skills' => [
                'Digital Marketing Strategy, SEO & SEM, Google Analytics & SEMrush',
                'Social Media Marketing, Content Creation & Copywriting, Budget Management, Data Analysis',
            ],
            'interests' => [
                'Travel & Culture',
                'Public Speaking',
                'Marathon Running',
            ],
            'certifications' => [
                'Google Analytics Certified',
                'Facebook Blueprint Certification',
                'HubSpot Inbound Marketing Certification',
            ],
        ];
    }
}
