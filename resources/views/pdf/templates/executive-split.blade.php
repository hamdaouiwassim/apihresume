<div class="exec-template" data-cv-preview="true">
    <style>
        .exec-template {
            font-family: 'Source Sans Pro', 'Segoe UI', Arial, sans-serif;
            background: #fff;
            padding: 24px;
            color: #0f172a;
            font-size: 12.5px;
            line-height: 1.45;
        }
        .exec-template * {
            box-sizing: border-box;
        }
        .exec-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .exec-name {
            font-size: 30px;
            letter-spacing: 0.18em;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .exec-role {
            text-align: center;
            margin-top: 6px;
            margin-bottom: 60px;
            font-size: 14px;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: #475569;
        }
        .exec-body {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .exec-sidebar {
            display: table-cell;
            width: 33.333%;
            border-right: 1px solid #e2e8f0;
            padding-right: 18px;
            vertical-align: top;
        }
        .exec-main {
            display: table-cell;
            width: 66.667%;
            padding-left: 24px;
            vertical-align: top;
        }
        .exec-section {
            margin-bottom: 20px;
        }
        .exec-section h3,
        .section-title {
            font-size: 11px;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            margin: 0 0 10px;
            color: #1e293b;
        }
        .exec-contact-item {
            margin-bottom: 8px;
        }
        .exec-contact-label {
            font-weight: 600;
            font-size: 14px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            display: block;
        }
        .exec-contact-value {
            font-size: 11px;
            color: #0f172a;
        }
        .exec-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .exec-list li {
            font-size: 11px;
            margin-bottom: 5px;
            color: #0f172a;
        }
        .exec-summary {
            font-size: 14px;
            line-height: 1.5;
            color: #1e293b;
            margin-bottom: 18px;
        }
        .exec-role {
            margin-bottom: 4px;
        }
        .exec-role-title {
            font-size: 17px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 0;
            text-align: left;
        }
        .exec-timeline {
            font-size: 14px;
            color: #64748b;
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
        }
        .exec-company {
            font-size: 12px;
            color: #475569;
            margin: 0 0 8px;
            text-align: left;
            width: 100%;
        }
        .exec-bullets {
            text-align: justify;
            letter-spacing: 0;
            text-transform: lowercase;
            font-family: Arial, sans-serif !important;
            margin: 0 0 14px 18px;
            padding: 0;
        }
        .exec-bullets li {
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>

    <div class="exec-header">
        @if(!empty($resume['name']))
            <h1 class="exec-name">{{ strtoupper($resume['name']) }}</h1>
        @endif
        @if(!empty($resume['tagline']))
            <p class="exec-role">{{ strtoupper($resume['tagline']) }}</p>
        @endif
    </div>

    @php
    // Define available sidebar sections
    $availableSidebarSections = [
        'personal' => [
            'hasData' => !empty($contactItems),
            'render' => function() use ($contactItems, $strings) {
                if (empty($contactItems)) return '';

                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['contact'] ?? 'Contact') . '</h3>';
                foreach($contactItems as $item) {
                    $html .= '<div class="exec-contact-item">';
                    $html .= '<span class="exec-contact-label">' . strtoupper($item['label']) . '</span>';
                    $html .= '<span class="exec-contact-value">' . $item['value'] . '</span>';
                    $html .= '</div>';
                }
                $html .= '</section>';
                return $html;
            }
        ],
        'education' => [
            'hasData' => !empty($education),
            'render' => function() use ($education, $strings) {
                if (empty($education)) return '';

                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['education'] ?? 'Education') . '</h3>';
                $html .= '<ul class="exec-list">';
                foreach($education as $edu) {
                    $html .= '<li><strong>' . ($edu['degree'] ?? '') . '</strong>';
                    if (!empty($edu['school'])) $html .= ' | ' . $edu['school'];
                    if (!empty($edu['location'])) $html .= ' | ' . $edu['location'];
                    if (!empty($edu['graduated'])) {
                        $html .= ' (' . (($strings['graduated'] ?? 'Graduated') . ' ' . $edu['graduated']) . ')';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'skills' => [
            'hasData' => !empty($skills),
            'render' => function() use ($skills, $strings) {
                if (empty($skills)) return '';

                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['skills'] ?? 'Skills') . '</h3>';
                $html .= '<ul class="exec-list">';
                foreach($skills as $skillLine) {
                    $html .= '<li>' . $skillLine . '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'certificates' => [
            'hasData' => true, // Always show if in section order
            'render' => function() use ($certifications, $strings) {
                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['certifications'] ?? 'Certifications') . '</h3>';
                if (!empty($certifications)) {
                    $html .= '<ul class="exec-list">';
                    foreach($certifications as $cert) {
                        $html .= '<li>' . $cert . '</li>';
                    }
                    $html .= '</ul>';
                }
                $html .= '</section>';
                return $html;
            }
        ],
        'languages' => [
            'hasData' => !empty($languages),
            'render' => function() use ($languages, $strings) {
                if (empty($languages)) return '';

                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['languages'] ?? 'Languages') . '</h3>';
                $html .= '<ul class="exec-list">';
                foreach($languages as $language) {
                    $html .= '<li>' . $language . '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'hobbies' => [
            'hasData' => !empty($hobbies),
            'render' => function() use ($hobbies, $strings) {
                if (empty($hobbies)) return '';

                $html = '<section class="exec-section">';
                $html .= '<h3>' . ($strings['hobbies'] ?? 'Hobbies') . '</h3>';
                $html .= '<ul class="exec-list">';
                foreach($hobbies as $hobby) {
                    $html .= '<li>' . $hobby . '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'socialMedia' => [
            'hasData' => false, // Social media handled in personal/contact
            'render' => function() { return ''; }
        ],
    ];

    // Define available main sections
    $availableMainSections = [
        'personal' => [
            'hasData' => !empty($resume['summary']),
            'render' => function() use ($resume, $strings) {
                if (empty($resume['summary'])) return '';

                $html = '<div class="exec-section">';
                $html .= '<h3 class="section-title">' . ($strings['professional_summary'] ?? 'Professional Summary') . '</h3>';
                $html .= '<p class="exec-summary">' . $resume['summary'] . '</p>';
                $html .= '</div>';
                return $html;
            }
        ],
        'experience' => [
            'hasData' => !empty($experience),
            'render' => function() use ($experience, $strings, $formatTimeline) {
                if (empty($experience)) return '';

                $html = '<div class="exec-section">';
                $html .= '<h3 class="section-title">' . ($strings['work_experience'] ?? 'Work Experience') . '</h3>';

                foreach($experience as $role) {
                    $timeline = $formatTimeline($role['start'] ?? null, $role['end'] ?? null);

                    $html .= '<div class="exec-role">';
                    if (!empty($role['title'])) {
                        $html .= '<p class="exec-role-title">' . $role['title'] . '</p>';
                    }
                    if (!empty($timeline)) {
                        $html .= '<p class="exec-timeline">' . $timeline . '</p>';
                    }
                    if (!empty($role['company']) || !empty($role['location'])) {
                        $html .= '<p class="exec-company">' . ($role['company'] ?? '') . (!empty($role['location']) ? ' | ' . $role['location'] : '') . '</p>';
                    }
                    if (!empty($role['summary'])) {
                        $html .= '<p class="exec-summary">' . $role['summary'] . '</p>';
                    }
                    if (!empty($role['bullets'])) {
                        $html .= '<ul class="exec-bullets">';
                        foreach($role['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '</div>';
                }

                $html .= '</div>';
                return $html;
            }
        ],
        'projects' => [
            'hasData' => !empty($projects),
            'render' => function() use ($projects, $strings, $formatTimeline) {
                if (empty($projects)) return '';

                $html = '<div class="exec-section">';
                $html .= '<h3 class="section-title">' . ($strings['projects'] ?? 'Projects') . '</h3>';

                foreach($projects as $project) {
                    $timeline = $formatTimeline($project['start'] ?? null, $project['end'] ?? null);

                    $html .= '<div class="exec-role">';
                    if (!empty($project['name'])) {
                        $html .= '<p class="exec-role-title">' . $project['name'] . '</p>';
                    }
                    if (!empty($timeline)) {
                        $html .= '<p class="exec-timeline">' . $timeline . '</p>';
                    }
                    if (!empty($project['technologies'])) {
                        $html .= '<p class="exec-company">' . $project['technologies'] . '</p>';
                    }
                    if (!empty($project['url'])) {
                        $html .= '<p class="exec-company" style="font-size: 11px; color: #6B7280;">' . $project['url'] . '</p>';
                    }
                    if (!empty($project['description'])) {
                        $html .= '<p class="exec-summary">' . $project['description'] . '</p>';
                    }
                    if (!empty($project['bullets'])) {
                        $html .= '<ul class="exec-bullets">';
                        foreach($project['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '</div>';
                }

                $html .= '</div>';
                return $html;
            }
        ],
        'education' => ['hasData' => false, 'render' => function() { return ''; }], // Education is in sidebar
        'skills' => ['hasData' => false, 'render' => function() { return ''; }], // Skills is in sidebar
        'certifications' => ['hasData' => false, 'render' => function() { return ''; }], // Certifications is in sidebar
        'languages' => ['hasData' => false, 'render' => function() { return ''; }], // Languages is in sidebar
        'hobbies' => ['hasData' => false, 'render' => function() { return ''; }], // Hobbies is in sidebar
        'socialMedia' => ['hasData' => false, 'render' => function() { return ''; }], // Social media handled in contact
    ];

    // Build ordered sidebar sections respecting sectionOrder
    $orderedSidebarSections = [];
    foreach ($sectionOrder as $sectionKey) {
        if (isset($availableSidebarSections[$sectionKey])) {
            // Always include certifications, but only include others if they have data
            if ($sectionKey === 'certifications' || $availableSidebarSections[$sectionKey]['hasData']) {
                $orderedSidebarSections[] = [
                    'key' => $sectionKey,
                    'render' => $availableSidebarSections[$sectionKey]['render']
                ];
            }
        }
    }

    // Build ordered main sections respecting sectionOrder
    $orderedMainSections = [];
    foreach ($sectionOrder as $sectionKey) {
        if (isset($availableMainSections[$sectionKey])) {
            // Only include sections that have data (certifications is in sidebar, not main)
            if ($availableMainSections[$sectionKey]['hasData']) {
                $orderedMainSections[] = [
                    'key' => $sectionKey,
                    'render' => $availableMainSections[$sectionKey]['render']
                ];
            }
        }
    }
    @endphp

    <div class="exec-body">
        <aside class="exec-sidebar">
            @foreach($orderedSidebarSections as $section)
                {!! $section['render']() !!}
            @endforeach
        </aside>

        <section class="exec-main">
            @foreach($orderedMainSections as $section)
                {!! $section['render']() !!}
            @endforeach
        </section>
    </div>
</div>

