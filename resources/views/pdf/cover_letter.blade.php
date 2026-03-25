<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $coverLetter->title }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 40px;
        }

        .recipient-info {
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .date {
            margin-bottom: 20px;
        }

        .subject {
            font-weight: bold;
            margin-bottom: 25px;
        }

        .content {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="recipient-info">
        @if($coverLetter->recipient_name) <strong>To: {{ $coverLetter->recipient_name }}</strong><br> @endif
        @if($coverLetter->recipient_company) {{ $coverLetter->recipient_company }}<br> @endif
        @if($coverLetter->recipient_address) {!! nl2br(e($coverLetter->recipient_address)) !!}<br> @endif
        @if($coverLetter->city || $coverLetter->country)
            {{ $coverLetter->city }}{{ $coverLetter->city && $coverLetter->country ? ', ' : '' }}{{ $coverLetter->country }}
        @endif
    </div>

    <div class="date">
        {{ $coverLetter->date ?: now()->format('F d, Y') }}
    </div>

    @if($coverLetter->subject)
        <div class="subject">
            Subject: {{ $coverLetter->subject }}
        </div>
    @endif

    <div class="content">
        {!! nl2br(e($coverLetter->content)) !!}
    </div>
</body>

</html>