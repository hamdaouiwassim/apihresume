<!DOCTYPE html>
<html lang="{{ $locale }}">

<head>
    <meta charset="UTF-8">
    <title>{{ $c->title }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #222;
            margin: 0;
            padding: 40px 48px;
        }

        h1 {
            font-size: 16pt;
            text-align: center;
            margin: 0 0 28px 0;
            letter-spacing: 0.04em;
        }

        .meta {
            text-align: right;
            margin-bottom: 24px;
            font-size: 10pt;
            color: #555;
        }

        p {
            margin: 0 0 12px 0;
            text-align: justify;
        }

        .company-block {
            margin: 18px 0;
            padding: 12px 14px;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .duties {
            margin-top: 16px;
        }

        .duties-title {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .signature {
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <h1>{{ $strings['heading'] }}</h1>

    <div class="meta">
        @if($c->letter_place){{ $c->letter_place }}, @endif{{ $letterDateFmt }}
    </div>

    <p><strong>{{ $strings['to_whom'] }}</strong></p>

    <p>
        {{ $strings['body_intro'] }}
        <strong>{{ $c->employee_name }}</strong>
        {{ $strings['was_employed'] }}
        <strong>{{ $c->company_name }}</strong>@if($c->employee_job_title), {{ $strings['as'] }}
            <strong>{{ $c->employee_job_title }}</strong>@endif.
        {{ $strings['from'] }} <strong>{{ $startFmt }}</strong>
        @if($c->is_current_employment)
            {{ $strings['to'] }} <strong>{{ $strings['present'] }}</strong>.
        @else
            {{ $strings['to'] }} <strong>{{ $endFmt }}</strong>.
        @endif
    </p>

    @if($c->company_address)
        <div class="company-block">
            {!! nl2br(e($c->company_address)) !!}
        </div>
    @endif

    @if($c->duties_summary)
        <div class="duties">
            <div class="duties-title">{{ $strings['duties'] }}</div>
            <p>{!! nl2br(e($c->duties_summary)) !!}</p>
        </div>
    @endif

    <p>{{ $strings['closing'] }}</p>

    <div class="signature">
        <p><strong>{{ $strings['signature_line'] }}</strong></p>
        @if($c->signer_name_title)
            <p>{{ $c->signer_name_title }}</p>
        @endif
    </div>
</body>

</html>
