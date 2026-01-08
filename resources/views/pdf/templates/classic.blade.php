<div class="resume-container" data-cv-preview="true">
    <style>
        .resume-container {
            width: 100%;
            min-height: 100vh;
        }

        .resume-inner {
            max-width: 780px;
            margin: 0 auto;
            padding: 18px 24px 24px;
            font-family: 'Source Sans Pro', 'Segoe UI', Arial, sans-serif;
        }

        h1 {
            font-size: 32px;
            letter-spacing: 0.08em;
            text-align: center;
            margin-bottom: 8px;
        }

        .tagline {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .contact-line {
            text-align: center;
            font-size: 12px;
            color: #374151;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-bottom: 2px solid #111827;
            padding-bottom: 6px;
            margin: 28px 0 12px;
        }

        h3 {
            font-size: 14px;
            margin: 0;
            font-weight: 700;
        }

        .subheading {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .section-table td {
            padding: 0 0 8px 0;
            vertical-align: top;
        }

        .section-table td:first-child {
            width: 70%;
            padding-right: 12px;
        }

        .section-table td:last-child {
            width: 30%;
            text-align: right;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .entry-spacer {
            height: 6px;
        }

        ul {
            margin: 6px 0 12px 18px;
            padding: 0;
        }

        ul li {
            margin-bottom: 4px;
        }

        .section {
            margin-bottom: 12px;
        }

        .list-simple {
            margin-left: 14px;
        }
    </style>

    @php
    // Define available sections with their data and render functions
    $availableSections = [
        'personal' => [
            'hasData' => !empty($resume['name']) || !empty($resume['tagline']) || !empty($contactLine) || !empty($resume['summary']),
            'render' => function() use ($resume, $contactLine, $strings) {
                $html = '';

                if (!empty($resume['name'])) {
                    $html .= '<h1>' . strtoupper($resume['name']) . '</h1>';
                }

                if (!empty($resume['tagline'])) {
                    $html .= '<p class="tagline">' . $resume['tagline'] . '</p>';
                }

                if (!empty($contactLine)) {
                    $html .= '<p class="contact-line">' . implode(' | ', $contactLine) . '</p>';
                }

                if (!empty($resume['summary'])) {
                    $html .= '<h2>' . ($strings['professional_summary'] ?? 'Professional Summary') . '</h2>';
                    $html .= '<p>' . $resume['summary'] . '</p>';
                }

                return $html;
            }
        ],
        'socialMedia' => [
            'hasData' => false, // Social media is handled in contact line for classic template
            'render' => function() { return ''; }
        ],
        'experience' => [
            'hasData' => !empty($experience),
            'render' => function() use ($experience, $strings) {
                if (empty($experience)) return '';

                $html = '<h2>' . ($strings['work_experience'] ?? 'Work Experience') . '</h2>';
                $html .= '<table class="section-table">';

                foreach($experience as $role) {
                    $html .= '<tr><td>';

                    if (!empty($role['title'])) {
                        $html .= '<h3>' . $role['title'] . '</h3>';
                    }

                    $subheading = $role['company'] ?? '';
                    if (!empty($role['location'])) {
                        $subheading .= ($subheading ? ', ' : '') . $role['location'];
                    }

                    if ($subheading) {
                        $html .= '<p class="subheading">' . $subheading . '</p>';
                    }

                    if (!empty($role['summary'])) {
                        $html .= '<p>' . $role['summary'] . '</p>';
                    }

                    if (!empty($role['bullets'])) {
                        $html .= '<ul>';
                        foreach($role['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }

                    $html .= '</td><td>';

                    if (!empty($role['start']) || !empty($role['end'])) {
                        $html .= ($role['start'] ?? '') . (!empty($role['end']) ? ' – ' . $role['end'] : '');
                    }

                    $html .= '</td></tr><tr><td colspan="2" class="entry-spacer"></td></tr>';
                }

                $html .= '</table>';
                return $html;
            }
        ],
        'education' => [
            'hasData' => !empty($education),
            'render' => function() use ($education, $strings) {
                if (empty($education)) return '';

                $html = '<h2>' . ($strings['education'] ?? 'Education') . '</h2>';
                $html .= '<table class="section-table">';

                foreach($education as $edu) {
                    $html .= '<tr><td>';

                    if (!empty($edu['degree'])) {
                        $html .= '<h3>' . $edu['degree'] . '</h3>';
                    }

                    $subheading = $edu['school'] ?? '';
                    if (!empty($edu['location'])) {
                        $subheading .= ', ' . $edu['location'];
                    }

                    if ($subheading) {
                        $html .= '<p class="subheading">' . $subheading . '</p>';
                    }

                    if (!empty($edu['details'])) {
                        $html .= '<p>' . $edu['details'] . '</p>';
                    }

                    $html .= '</td><td>';

                    if (!empty($edu['graduated'])) {
                        $html .= ($strings['graduated'] ?? 'Graduated') . ': ' . $edu['graduated'];
                    }

                    $html .= '</td></tr><tr><td colspan="2" class="entry-spacer"></td></tr>';
                }

                $html .= '</table>';
                return $html;
            }
        ],
        'skills' => [
            'hasData' => !empty($skills),
            'render' => function() use ($skills, $strings) {
                if (empty($skills)) return '';

                $html = '<h2>' . ($strings['skills'] ?? 'Skills') . '</h2>';
                $html .= '<ul class="list-simple">';
                foreach($skills as $skillLine) {
                    $html .= '<li>' . $skillLine . '</li>';
                }
                $html .= '</ul>';
                return $html;
            }
        ],
        'hobbies' => [
            'hasData' => !empty($interests) || !empty($hobbies),
            'render' => function() use ($interests, $hobbies, $strings) {
                $allItems = array_filter(array_merge($interests, $hobbies));
                if (empty($allItems)) return '';

                $html = '<h2>' . ($strings['interests'] ?? 'Interests') . '</h2>';
                $html .= '<ul class="list-simple">';
                foreach($allItems as $item) {
                    $html .= '<li>' . $item . '</li>';
                }
                $html .= '</ul>';
                return $html;
            }
        ],
        'certificates' => [
            'hasData' => true, // Always show if in section order
            'render' => function() use ($certifications, $strings) {
                $html = '<h2>' . ($strings['certifications'] ?? 'Certifications') . '</h2>';
                if (!empty($certifications)) {
                    $html .= '<ul class="list-simple">';
                    foreach($certifications as $cert) {
                        $html .= '<li>' . $cert . '</li>';
                    }
                    $html .= '</ul>';
                }
                return $html;
            }
        ],
        'languages' => [
            'hasData' => !empty($languages),
            'render' => function() use ($languages, $strings) {
                if (empty($languages)) return '';

                $html = '<h2>' . ($strings['languages'] ?? 'Languages') . '</h2>';
                $html .= '<ul class="list-simple">';
                foreach($languages as $language) {
                    $html .= '<li>' . $language . '</li>';
                }
                $html .= '</ul>';
                return $html;
            }
        ],
        'projects' => [
            'hasData' => !empty($projects),
            'render' => function() use ($projects, $strings) {
                if (empty($projects)) return '';

                $html = '<h2>' . ($strings['projects'] ?? 'Projects') . '</h2>';
                $html .= '<table class="section-table">';

                foreach($projects as $project) {
                    $html .= '<tr><td>';

                    if (!empty($project['name'])) {
                        $html .= '<h3>' . $project['name'] . '</h3>';
                    }

                    if (!empty($project['technologies'])) {
                        $html .= '<p class="subheading">' . $project['technologies'] . '</p>';
                    }

                    if (!empty($project['description'])) {
                        $html .= '<p>' . $project['description'] . '</p>';
                    }

                    if (!empty($project['bullets'])) {
                        $html .= '<ul>';
                        foreach($project['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }

                    if (!empty($project['url'])) {
                        $html .= '<p style="font-size: 11px; color: #6B7280; margin-top: 4px;">' . $project['url'] . '</p>';
                    }

                    $html .= '</td><td>';

                    if (!empty($project['start']) || !empty($project['end'])) {
                        $html .= ($project['start'] ?? '') . (!empty($project['end']) ? ' – ' . $project['end'] : '');
                    }

                    $html .= '</td></tr><tr><td colspan="2" class="entry-spacer"></td></tr>';
                }

                $html .= '</table>';
                return $html;
            }
        ],
    ];

    // Build ordered sections respecting sectionOrder
    $orderedSections = [];
    foreach ($sectionOrder as $sectionKey) {
        if (isset($availableSections[$sectionKey])) {
            $hasData = $availableSections[$sectionKey]['hasData'];
            $shouldInclude = ($sectionKey === 'certifications' || $hasData);
            if ($shouldInclude) {
                $orderedSections[] = [
                    'key' => $sectionKey,
                    'render' => $availableSections[$sectionKey]['render']
                ];
            }
        }
    }
    @endphp

    <div class="resume-inner">
        @foreach($orderedSections as $section)
            {!! $section['render']() !!}
        @endforeach
    </div>
</div>

