<div class="modern-template" data-cv-preview="true">
    <style>
        .modern-template {
            font-family: 'Segoe UI', Arial, sans-serif;
            
            color: #333;
            font-size: 13px;
            line-height: 1.5;
        }
        .modern-template * {
            box-sizing: border-box;
        }
        .modern-body {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            background: #fff;
            padding: 24px;
            border-radius: 4px;
        }
        .modern-sidebar {
            display: table-cell;
            width: 280px;
            vertical-align: top;
            padding-right: 20px;
        }
        .modern-main {
            display: table-cell;
            vertical-align: top;
            padding-left: 20px;
        }
        .modern-profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 16px;
            display: block;
            border: 3px solid #e0e0e0;
        }
        .modern-section {
            margin-bottom: 20px;
        }
        .modern-section-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 12px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 6px;
        }
        .modern-contact-item {
            margin-bottom: 10px;
            font-size: 12px;
        }
        .modern-contact-label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 2px;
        }
        .modern-contact-value {
            color: #333;
            word-break: break-word;
        }
        .modern-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .modern-list li {
            font-size: 12px;
            margin-bottom: 6px;
            color: #333;
            padding-left: 0;
        }
        .modern-header {
            margin-bottom: 16px;
        }
        .modern-name {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 4px;
            color: #333;
        }
        .modern-title {
            font-size: 16px;
            color: #666;
            margin: 0 0 8px;
        }
        .modern-location {
            font-size: 13px;
            color: #666;
        }
        .modern-summary {
            font-size: 13px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
        }
        .modern-item {
            margin-bottom: 16px;
        }
        .modern-item-header {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .modern-item-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            display: table-cell;
        }
        .modern-item-date {
            font-size: 12px;
            color: #666;
            white-space: nowrap;
            text-align: right;
            display: table-cell;
            padding-left: 10px;
        }
        .modern-item-subtitle {
            font-size: 12px;
            color: #666;
            margin: 0 0 8px;
        }
        .modern-item-description {
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            margin: 0 0 8px;
        }
        .modern-bullets {
            margin: 0 0 12px 16px;
            padding: 0;
            list-style: disc;
        }
        .modern-bullets li {
            font-size: 12px;
            margin-bottom: 4px;
            color: #333;
        }
    </style>

    @php
    // Define available sidebar sections
    $availableSidebarSections = [
        'personal' => [
            'hasData' => !empty($contactItems),
            'render' => function() use ($contactItems, $strings) {
                if (empty($contactItems)) return '';

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['contact'] ?? 'Contact') . '</h3>';
                foreach($contactItems as $item) {
                    $html .= '<div class="modern-contact-item">';
                    $html .= '<span class="modern-contact-label">' . $item['label'] . '</span>';
                    $html .= '<span class="modern-contact-value">' . $item['value'] . '</span>';
                    $html .= '</div>';
                }
                $html .= '</section>';
                return $html;
            }
        ],
        'skills' => [
            'hasData' => !empty($skills),
            'render' => function() use ($skills, $strings) {
                if (empty($skills)) return '';

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['skills'] ?? 'Technical Skills') . '</h3>';
                $html .= '<ul class="modern-list">';
                foreach($skills as $skillLine) {
                    $html .= '<li>' . $skillLine . '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'certificates' => [
            'hasData' => !empty($certifications),
            'render' => function() use ($certifications, $strings) {
                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['certifications'] ?? 'Activity & Achievement') . '</h3>';
                if (!empty($certifications)) {
                    $html .= '<ul class="modern-list">';
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

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['languages'] ?? 'Languages') . '</h3>';
                $html .= '<ul class="modern-list">';
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

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['hobbies'] ?? 'Hobbies') . '</h3>';
                $html .= '<ul class="modern-list">';
                foreach($hobbies as $hobby) {
                    $html .= '<li>' . $hobby . '</li>';
                }
                $html .= '</ul></section>';
                return $html;
            }
        ],
        'socialMedia' => [
            'hasData' => false,
            'render' => function() { return ''; }
        ],
    ];

    // Define available main sections
    $availableMainSections = [
        'personal' => [
            'hasData' => !empty($resume['summary']),
            'render' => function() use ($resume, $strings) {
                if (empty($resume['summary'])) return '';

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['professional_summary'] ?? 'About Me') . '</h3>';
                $html .= '<p class="modern-summary">' . $resume['summary'] . '</p>';
                $html .= '</section>';
                return $html;
            }
        ],
        'experience' => [
            'hasData' => !empty($experience),
            'render' => function() use ($experience, $strings, $formatTimeline) {
                if (empty($experience)) return '';

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['work_experience'] ?? 'Freelance Experience') . '</h3>';

                foreach($experience as $role) {
                    $timeline = $formatTimeline($role['start'] ?? null, $role['end'] ?? null);

                    $html .= '<div class="modern-item">';
                    $html .= '<div class="modern-item-header">';
                    if (!empty($role['title'])) {
                        $html .= '<strong class="modern-item-title">' . $role['title'] . '</strong>';
                    }
                    if (!empty($timeline)) {
                        $html .= '<span class="modern-item-date">' . $timeline . '</span>';
                    }
                    $html .= '</div>';
                    if (!empty($role['company']) || !empty($role['location'])) {
                        $html .= '<p class="modern-item-subtitle">' . ($role['company'] ?? '') . (!empty($role['location']) ? ' | ' . $role['location'] : '') . '</p>';
                    }
                    if (!empty($role['summary'])) {
                        $html .= '<p class="modern-item-description">' . $role['summary'] . '</p>';
                    }
                    if (!empty($role['bullets'])) {
                        $html .= '<ul class="modern-bullets">';
                        foreach($role['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }
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

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['education'] ?? 'Education') . '</h3>';

                foreach($education as $edu) {
                    $html .= '<div class="modern-item">';
                    $html .= '<div class="modern-item-header">';
                    if (!empty($edu['degree'])) {
                        $html .= '<strong class="modern-item-title">' . $edu['degree'] . '</strong>';
                    }
                    if (!empty($edu['graduated'])) {
                        $html .= '<span class="modern-item-date">' . $edu['graduated'] . '</span>';
                    }
                    $html .= '</div>';
                    if (!empty($edu['school']) || !empty($edu['location'])) {
                        $html .= '<p class="modern-item-subtitle">' . ($edu['school'] ?? '') . (!empty($edu['location']) ? ' | ' . $edu['location'] : '') . '</p>';
                    }
                    $html .= '</div>';
                }

                $html .= '</section>';
                return $html;
            }
        ],
        'projects' => [
            'hasData' => !empty($projects),
            'render' => function() use ($projects, $strings, $formatTimeline) {
                if (empty($projects)) return '';

                $html = '<section class="modern-section">';
                $html .= '<h3 class="modern-section-title">' . ($strings['projects'] ?? 'Projects') . '</h3>';

                foreach($projects as $project) {
                    $timeline = $formatTimeline($project['start'] ?? null, $project['end'] ?? null);

                    $html .= '<div class="modern-item">';
                    $html .= '<div class="modern-item-header">';
                    if (!empty($project['name'])) {
                        $html .= '<strong class="modern-item-title">' . $project['name'] . '</strong>';
                    }
                    if (!empty($timeline)) {
                        $html .= '<span class="modern-item-date">' . $timeline . '</span>';
                    }
                    $html .= '</div>';
                    if (!empty($project['technologies'])) {
                        $html .= '<p class="modern-item-subtitle">' . $project['technologies'] . '</p>';
                    }
                    if (!empty($project['description'])) {
                        $html .= '<p class="modern-item-description">' . $project['description'] . '</p>';
                    }
                    if (!empty($project['bullets'])) {
                        $html .= '<ul class="modern-bullets">';
                        foreach($project['bullets'] as $bullet) {
                            $html .= '<li>' . $bullet . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '</div>';
                }

                $html .= '</section>';
                return $html;
            }
        ],
        'skills' => ['hasData' => false, 'render' => function() { return ''; }],
        'certifications' => ['hasData' => false, 'render' => function() { return ''; }],
        'languages' => ['hasData' => false, 'render' => function() { return ''; }],
        'hobbies' => ['hasData' => false, 'render' => function() { return ''; }],
        'socialMedia' => ['hasData' => false, 'render' => function() { return ''; }],
    ];

    // Build ordered sidebar sections respecting sectionOrder
    $orderedSidebarSections = [];
    foreach ($sectionOrder as $sectionKey) {
        if (isset($availableSidebarSections[$sectionKey])) {
            if ($sectionKey === 'certificates' || $availableSidebarSections[$sectionKey]['hasData']) {
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
            if ($availableMainSections[$sectionKey]['hasData']) {
                $orderedMainSections[] = [
                    'key' => $sectionKey,
                    'render' => $availableMainSections[$sectionKey]['render']
                ];
            }
        }
    }

    // Get profile picture from contact
    $profilePicture = null;
    foreach($contactItems as $item) {
        if (isset($item['label']) && strtolower($item['label']) === 'profilepicture') {
            $profilePicture = $item['value'] ?? null;
            break;
        }
    }
    // Also check resume data directly
    if (empty($profilePicture) && !empty($resume['contact']['profile_picture'])) {
        $profilePicture = $resume['contact']['profile_picture'];
    }
    @endphp

    <div class="modern-body">
        <aside class="modern-sidebar">
            @if(!empty($profilePicture))
                <img src="{{ $profilePicture }}" alt="{{ $resume['name'] ?? 'Profile' }}" class="modern-profile-picture" />
            @endif

            @foreach($orderedSidebarSections as $section)
                {!! $section['render']() !!}
            @endforeach
        </aside>

        <section class="modern-main">
            <div class="modern-header">
                @if(!empty($resume['name']))
                    <h1 class="modern-name">{{ $resume['name'] }}</h1>
                @endif
                @if(!empty($resume['tagline']))
                    <p class="modern-title">{{ $resume['tagline'] }}</p>
                @endif
                @if(!empty($contactItems))
                    @foreach($contactItems as $item)
                        @if(strtolower($item['label']) === 'location')
                            <p class="modern-location">📍 {{ $item['value'] }}</p>
                            @break
                        @endif
                    @endforeach
                @endif
            </div>

            @foreach($orderedMainSections as $section)
                {!! $section['render']() !!}
            @endforeach
        </section>
    </div>
</div>
