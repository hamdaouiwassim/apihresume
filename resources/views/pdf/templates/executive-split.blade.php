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

    <div class="exec-body">
        <aside class="exec-sidebar">
            @if(!empty($contactItems))
                <section class="exec-section">
                    <h3>{{ $strings['contact'] ?? 'Contact' }}</h3>
                    @foreach($contactItems as $item)
                        <div class="exec-contact-item">
                            <span class="exec-contact-label">{{ strtoupper($item['label']) }}</span>
                            <span class="exec-contact-value">{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </section>
            @endif

            @if(!empty($education))
                <section class="exec-section">
                    <h3>{{ $strings['education'] ?? 'Education' }}</h3>
                    <ul class="exec-list">
                        @foreach($education as $edu)
                            <li>
                                <strong>{{ $edu['degree'] ?? '' }}</strong>
                                @if(!empty($edu['school'])) | {{ $edu['school'] }} @endif
                                @if(!empty($edu['location'])) | {{ $edu['location'] }} @endif
                                @if(!empty($edu['graduated']))
                                    ({{ ($strings['graduated'] ?? 'Graduated') . ' ' . $edu['graduated'] }})
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($skills))
                <section class="exec-section">
                    <h3>{{ $strings['skills'] ?? 'Skills' }}</h3>
                    <ul class="exec-list">
                        @foreach($skills as $skillLine)
                            <li>{{ $skillLine }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($certifications))
                <section class="exec-section">
                    <h3>{{ $strings['certifications'] ?? 'Certifications' }}</h3>
                    <ul class="exec-list">
                        @foreach($certifications as $cert)
                            <li>{{ $cert }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($languages))
                <section class="exec-section">
                    <h3>{{ $strings['languages'] ?? 'Languages' }}</h3>
                    <ul class="exec-list">
                        @foreach($languages as $language)
                            <li>{{ $language }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($hobbies))
                <section class="exec-section">
                    <h3>{{ $strings['hobbies'] ?? 'Hobbies' }}</h3>
                    <ul class="exec-list">
                        @foreach($hobbies as $hobby)
                            <li>{{ $hobby }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(!empty($interests))
                <section class="exec-section">
                    <h3>{{ $strings['interests'] ?? 'Interests' }}</h3>
                    <ul class="exec-list">
                        @foreach($interests as $interest)
                            <li>{{ $interest }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>

        <section class="exec-main">
            @if(!empty($resume['summary']))
                <div class="exec-section">
                    <h3 class="section-title">{{ $strings['professional_summary'] ?? 'Professional Summary' }}</h3>
                    <p class="exec-summary">{{ $resume['summary'] }}</p>
                </div>
            @endif

            @if(!empty($experience))
                <div class="exec-section">
                    <h3 class="section-title">{{ $strings['work_experience'] ?? 'Work Experience' }}</h3>
                    @foreach($experience as $role)
                        @php
                        
                            $timeline = $formatTimeline($role['start'] ?? null, $role['end'] ?? null);
                        
                        @endphp
                        <div class="exec-role">
                            @if(!empty($role['title']))
                                <p class="exec-role-title">{{ $role['title'] }}</p>
                            @endif
                            @if(!empty($timeline))
                                <p class="exec-timeline">{{ $timeline }}</p>
                            @endif
                            @if(!empty($role['company']) || !empty($role['location']))
                                <p class="exec-company">
                                    {{ $role['company'] ?? '' }}
                                    @if(!empty($role['location']))
                                        | {{ $role['location'] }}
                                    @endif
                                </p>
                            @endif
                            @if(!empty($role['summary']))
                                <p class="exec-summary">{{ $role['summary'] }}</p>
                            @endif
                            @if(!empty($role['bullets']))
                                <ul class="exec-bullets">
                                    @foreach($role['bullets'] as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>

