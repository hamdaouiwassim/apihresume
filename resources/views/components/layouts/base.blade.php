@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'imageAlt' => null,
    'imageWidth' => null,
    'imageHeight' => null,
    'ogType' => 'website',
    'robots' => 'index, follow',
    'jsonLd' => null,
    'bodyClass' => '',
    'islands' => false,
])
@php
    // Props forwarded from the guest/app/admin wrappers via {{ $attributes }} keep their kebab-case
    // names (e.g. json-ld), which Blade assigns to ${'json-ld'} instead of $jsonLd. Normalize them.
    foreach (['image-alt' => 'imageAlt', 'image-width' => 'imageWidth', 'image-height' => 'imageHeight', 'og-type' => 'ogType', 'json-ld' => 'jsonLd', 'body-class' => 'bodyClass'] as $__kebab => $__camel) {
        if (isset(${$__kebab})) {
            ${$__camel} = ${$__kebab};
        }
    }
    unset($__kebab, $__camel);

    $siteOrigin = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $defaultTitle = 'HResume - Free CV, Cover Letter & Work Certificate Builder';
    $defaultDescription = 'Create ATS-friendly CVs, professional cover letters, and employment work certificates for free with HResume. Templates, live preview, and PDF export. No credit card required.';
    $pageTitle = $title ?: $defaultTitle;
    $pageDescription = $description ?: $defaultDescription;
    $canonicalUrl = $canonical
        ? (preg_match('#^https?://#i', $canonical) ? $canonical : $siteOrigin.'/'.ltrim($canonical, '/'))
        : $siteOrigin.'/'.ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    $ogImage = $image
        ? (preg_match('#^https?://#i', $image) ? $image : (str_starts_with($image, '//') ? 'https:'.$image : $siteOrigin.'/'.ltrim($image, '/')))
        : $siteOrigin.'/og-image.jpg';
    $locale = app()->getLocale();
    $viteEntries = ['resources/css/app.css', 'resources/js/app.js'];
    if ($islands) {
        $viteEntries[] = 'resources/js/islands.jsx';
    }
    // Shared with Alpine + React islands (replaces the SPA's /me call and LanguageContext state).
    $appBootstrap = json_encode([
        'locale' => $locale,
        'user' => auth()->user()?->loadMissing(['candidate', 'admin']),
        'siteOrigin' => $siteOrigin,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/logo.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <title>{{ $pageTitle }}</title>
    <meta name="title" content="{{ $pageTitle }}">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="resume builder, CV builder, cover letter builder, work certificate generator, attestation de travail, free resume maker, ATS friendly resume, resume templates, lettre de motivation, create resume online, download resume PDF, job application">
    <meta name="author" content="HResume">
    <meta name="robots" content="{{ $robots }}">
    <meta name="language" content="English, French">

    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    @if ($image && $imageWidth && $imageHeight)
        <meta property="og:image:width" content="{{ $imageWidth }}">
        <meta property="og:image:height" content="{{ $imageHeight }}">
    @elseif (! $image)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta property="og:image:alt" content="{{ $imageAlt ?: $pageTitle }}">
    <meta property="og:site_name" content="HResume">
    <meta property="og:locale" content="{{ $locale === 'fr' ? 'fr_FR' : 'en_US' }}">
    <meta property="og:locale:alternate" content="{{ $locale === 'fr' ? 'en_US' : 'fr_FR' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $canonicalUrl }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $imageAlt ?: $pageTitle }}">

    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HResume">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml">

    @if ($jsonLd)
        <script type="application/ld+json">{!! is_string($jsonLd) ? $jsonLd : json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif

    {{ $head ?? '' }}

    <script>
        window.__APP__ = {!! $appBootstrap !!};
        window.__toastQueue = [];
    </script>

    @if ($islands)
        @viteReactRefresh
    @endif
    @vite($viteEntries)
    @stack('head')

    @production
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-H3XY3952D3"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-H3XY3952D3');
        </script>
    @endproduction
</head>
<body class="{{ $bodyClass }}">
    {{ $slot }}

    <x-toaster />
    <x-confirm-dialog />
    <x-upgrade-pro-modal />

    @php
        $flashes = array_filter([
            'success' => session('success') ?? session('status'),
            'error' => session('error'),
            'info' => session('info'),
            'warning' => session('warning'),
        ]);
    @endphp
    @if ($flashes)
        <script>
            @foreach ($flashes as $type => $message)
                window.__toastQueue.push([@json($type), @json($message)]);
            @endforeach
        </script>
    @endif

    @stack('scripts')
</body>
</html>
