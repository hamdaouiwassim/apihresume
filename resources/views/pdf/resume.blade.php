@php
    $__typo = ($resume ?? [])['typography'] ?? [];
    $__bodyFontFamily = !empty($__typo['font_family']) ? $__typo['font_family'] : 'sans-serif';
    $__bodyFontSize = !empty($__typo['font_size']) ? (int) $__typo['font_size'] : 14;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 8mm 10mm; }
        * { box-sizing: border-box; }
        body {
            font-family: {!! $__bodyFontFamily !!};
            font-size: {{ $__bodyFontSize }}px;
            line-height: 1.5;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        p { margin: 0 0 6px; line-height: 1.4; }
        [data-cv-preview="true"] { padding: 0; background: transparent; }
    </style>
</head>
<body>
@php
    $resume = $resume ?? [];
    $contact = $resume['contact'] ?? [];
    $experience = $resume['experience'] ?? [];
    $education = $resume['education'] ?? [];
    $skills = $resume['skills'] ?? [];
    $interests = $resume['interests'] ?? [];
    $certifications = $resume['certifications'] ?? [];
    $languages = $resume['languages'] ?? [];
    $hobbies = $resume['hobbies'] ?? [];
    $projects = $resume['projects'] ?? [];
    $strings = $strings ?? [
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

    $contactLine = array_filter([
        $contact['location'] ?? null,
        $contact['email'] ?? null,
        $contact['phone'] ?? null,
        $contact['linkedin'] ?? null,
        $contact['github'] ?? null,
        $contact['website'] ?? null,
    ]);

    $contactItems = array_filter([
        ['label' => 'Email', 'value' => $contact['email'] ?? null],
        ['label' => 'Phone', 'value' => $contact['phone'] ?? null],
        ['label' => 'Location', 'value' => $contact['location'] ?? null],
        ['label' => 'LinkedIn', 'value' => $contact['linkedin'] ?? null],
        ['label' => 'GitHub', 'value' => $contact['github'] ?? null],
        ['label' => 'Website', 'value' => $contact['website'] ?? null],
        ['label' => 'ProfilePicture', 'value' => $contact['profile_picture'] ?? null],
    ], fn($item) => !empty($item['value'] ?? null));

    $presentLabel = $strings['present'] ?? 'Present';

    $formatTimelineLabel = function ($value) use ($presentLabel) {
        if (empty($value)) return '';
        if (preg_match('/present/i', $value)) return $presentLabel;
        try {
            $date = new \DateTime($value);
            return $date->format('M Y');
        } catch (\Exception $e) {
            return $value;
        }
    };

    $formatTimeline = function ($start, $end) use ($formatTimelineLabel) {
        $startLabel = $formatTimelineLabel($start);
        $endLabel = $formatTimelineLabel($end);
        if ($startLabel && $endLabel) return $startLabel . ' – ' . $endLabel;
        return $startLabel ?: $endLabel;
    };

    $templateView = $template_view ?? 'pdf.templates.classic';

    // Typography settings
    $typo = $resume['typography'] ?? [];
    $typoFontFamily = !empty($typo['font_family']) ? $typo['font_family'] : 'sans-serif';
    // Wrap font names with spaces in quotes for valid CSS (e.g. "DejaVu Sans")
    if (str_contains($typoFontFamily, ' ')) {
        $typoFontFamily = "'" . addslashes($typoFontFamily) . "'";
    }
    // Add fallback for custom fonts (font_id set) so Dompdf has a known fallback if lookup fails
    if (!empty($typo['font_id'])) {
        $typoFontFamily = $typoFontFamily . ', "DejaVu Sans", sans-serif';
    }
    $typoFontSize = !empty($typo['font_size']) ? (int) $typo['font_size'] : 14;
    // Scale factor: ratio between chosen size and the default 14px base
    $typoScale = $typoFontSize / 14;

@endphp

@include($templateView, [
    'resume' => $resume,
    'strings' => $strings,
    'contactLine' => $contactLine,
    'contactItems' => $contactItems,
    'experience' => $experience,
    'education' => $education,
    'skills' => $skills,
    'interests' => $interests,
    'certifications' => $certifications,
    'languages' => $languages,
    'hobbies' => $hobbies,
    'projects' => $projects,
    'sectionOrder' => $resume['section_order'] ?? ['personal', 'socialMedia', 'experience', 'education', 'skills', 'hobbies', 'certifications', 'languages', 'projects'],
    'formatTimeline' => $formatTimeline,
    'typoFontFamily' => $typoFontFamily,
    'typoFontSize' => $typoFontSize,
    'typoScale' => $typoScale,
])
</body>
</html>

