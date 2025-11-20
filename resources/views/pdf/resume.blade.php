<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        p {
            margin: 0 0 6px;
            line-height: 1.4;
        }

        .resume-container {
            width: 100%;
            min-height: 100vh;
        }

        .resume-inner {
            max-width: 780px;
            margin: 0 auto;
            padding: 18px 24px 24px;
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

        [data-cv-preview="true"] {
            padding: 0;
            background: transparent;
        }
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
        $strings = $strings ?? [
            'professional_summary' => 'Professional Summary',
            'work_experience' => 'Work Experience',
            'education' => 'Education',
            'skills' => 'Skills',
            'interests' => 'Interests',
            'certifications' => 'Certifications',
            'graduated' => 'Graduated',
        ];

        $contactLine = array_filter([
            $contact['location'] ?? null,
            $contact['email'] ?? null,
            $contact['phone'] ?? null,
            $contact['linkedin'] ?? null,
            $contact['website'] ?? null,
        ]);
    @endphp

    <div class="resume-container" data-cv-preview="true">
        <div class="resume-inner">
            @if(!empty($resume['name']))
                <h1>{{ strtoupper($resume['name']) }}</h1>
            @endif

            @if(!empty($resume['tagline']))
                <p class="tagline">{{ $resume['tagline'] }}</p>
            @endif

            @if(!empty($contactLine))
                <p class="contact-line">{{ implode(' | ', $contactLine) }}</p>
            @endif

            @if(!empty($resume['summary']))
                <h2>{{ $strings['professional_summary'] ?? 'Professional Summary' }}</h2>
                <p>{{ $resume['summary'] }}</p>
            @endif

            @if(!empty($experience))
                <h2>{{ $strings['work_experience'] ?? 'Work Experience' }}</h2>
                <table class="section-table">
                    @foreach($experience as $role)
                        <tr>
                            <td>
                                @if(!empty($role['title']))
                                    <h3>{{ $role['title'] }}</h3>
                                @endif
                                <p class="subheading">
                                    {{ $role['company'] ?? '' }}
                                    @if(!empty($role['location']))
                                        {{ $role['company'] ? ',' : '' }} {{ $role['location'] }}
                                    @endif
                                </p>
                                @if(!empty($role['summary']))
                                    <p>{{ $role['summary'] }}</p>
                                @endif
                                @if(!empty($role['bullets']))
                                    <ul>
                                        @foreach($role['bullets'] as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>
                                @if(!empty($role['start']) || !empty($role['end']))
                                    {{ $role['start'] ?? '' }}
                                    @if(!empty($role['end']))
                                        – {{ $role['end'] }}
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr><td colspan="2" class="entry-spacer"></td></tr>
                    @endforeach
                </table>
            @endif

            @if(!empty($education))
                <h2>{{ $strings['education'] ?? 'Education' }}</h2>
                <table class="section-table">
                    @foreach($education as $edu)
                        <tr>
                            <td>
                                @if(!empty($edu['degree']))
                                    <h3>{{ $edu['degree'] }}</h3>
                                @endif
                                <p class="subheading">
                                    {{ $edu['school'] ?? '' }}
                                    @if(!empty($edu['location']))
                                        , {{ $edu['location'] }}
                                    @endif
                                </p>
                                @if(!empty($edu['details']))
                                    <p>{{ $edu['details'] }}</p>
                                @endif
                            </td>
                            <td>
                                @if(!empty($edu['graduated']))
                                    {{ $strings['graduated'] ?? 'Graduated' }}: {{ $edu['graduated'] }}
                                @endif
                            </td>
                        </tr>
                        <tr><td colspan="2" class="entry-spacer"></td></tr>
                    @endforeach
                </table>
            @endif

            @if(!empty($skills))
                <h2>{{ $strings['skills'] ?? 'Skills' }}</h2>
                <ul class="skills-list">
                    @foreach($skills as $skillLine)
                        <li>{{ $skillLine }}</li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($interests))
                <h2>{{ $strings['interests'] ?? 'Interests' }}</h2>
                <ul class="list-simple">
                    @foreach($interests as $interest)
                        <li>{{ $interest }}</li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($certifications))
                <h2>{{ $strings['certifications'] ?? 'Certifications' }}</h2>
                <ul class="cert-list">
                    @foreach($certifications as $cert)
                        <li>{{ $cert }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</body>
</html>

