{{-- Port of pages/welcome.jsx (home). Stats, templates and reviews are rendered server-side for SEO. --}}
@php
    $welcome = (array) t('welcome', [], []);
    $trust = (array) ($welcome['trust'] ?? []);
    $suite = (array) ($welcome['suite'] ?? []);
    $finalCta = (array) ($welcome['finalCta'] ?? []);
    $walkthrough = (array) ($welcome['walkthrough'] ?? []);
    $transformation = (array) ($welcome['transformation'] ?? []);
    $trustProof = (array) ($welcome['trustProof'] ?? []);
    $wf = (array) ($welcome['features'] ?? []);
    $tplStrings = (array) ($welcome['templates'] ?? []);
    $examples = array_slice((array) ($transformation['examples'] ?? []), 0, 2);
    $heroCompact = config('app.landing_hero_variant') === 'compact';
    $heroTitle = $heroCompact && ! empty($welcome['heroTitleCompact']) ? $welcome['heroTitleCompact'] : ($welcome['title'] ?? '');
    $heroSubtitle = $heroCompact && ! empty($welcome['heroSubtitleCompact']) ? $welcome['heroSubtitleCompact'] : ($welcome['subtitle'] ?? '');
    $showWalkthrough = (bool) config('app.show_walkthrough_section');
    $walkthroughVideo = (function (string $u) {
        $u = trim($u);
        if ($u === '' || str_contains($u, 'youtube.com/embed/')) return $u;
        return preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $u, $m) ? 'https://www.youtube.com/embed/'.$m[1] : $u;
    })((string) (config('app.walkthrough_video_url') ?: ($walkthrough['videoUrl'] ?? '')));
    $formatStat = fn (int $n) => $n >= 1000000 ? number_format($n / 1000000, 1).'M+' : ($n >= 1000 ? number_format($n / 1000, 1).'K+' : (string) $n);
    $featureCards = [
        ['icon' => 'file-text', 'grad' => 'from-blue-400 to-blue-600', 'title' => $wf['easy'] ?? '', 'desc' => $wf['easyDesc'] ?? '', 'ai' => $wf['easyAi'] ?? null],
        ['icon' => 'layout', 'grad' => 'from-purple-400 to-purple-600', 'title' => $wf['professional'] ?? '', 'desc' => $wf['professionalDesc'] ?? '', 'ai' => $wf['professionalAi'] ?? null],
        ['icon' => 'rocket', 'grad' => 'from-pink-400 to-pink-600', 'title' => $wf['ats'] ?? '', 'desc' => $wf['atsDesc'] ?? '', 'ai' => $wf['atsAi'] ?? null],
        ['icon' => 'check-circle', 'grad' => 'from-green-400 to-green-600', 'title' => $wf['export'] ?? '', 'desc' => $wf['exportDesc'] ?? '', 'ai' => $wf['exportAi'] ?? null],
        ['icon' => 'award', 'grad' => 'from-indigo-400 to-indigo-600', 'title' => $wf['multilingual'] ?? '', 'desc' => $wf['multilingualDesc'] ?? '', 'ai' => $wf['multilingualAi'] ?? null],
        ['icon' => 'wand-2', 'grad' => 'from-violet-500 to-fuchsia-600', 'title' => $wf['smartAiTitle'] ?? '', 'desc' => $wf['smartAiDesc'] ?? '', 'ai' => $wf['smartAiHint'] ?? null, 'highlight' => true],
    ];
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'HResume',
        'url' => rtrim((string) config('app.frontend_url'), '/'),
        'description' => 'Free job application suite: ATS-friendly CV builder, cover letter editor, and employment work certificate generator with PDF export.',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'featureList' => [
            'Free resume and CV builder', 'Cover letter generator', 'Work certificate (employment attestation) PDF',
            'ATS-friendly templates', 'PDF download', 'Multiple language support', 'Secure sharing links',
        ],
    ];
@endphp
<x-layouts.guest nav-variant="hero" canonical="/" :json-ld="$jsonLd">
    <div class="relative min-h-screen">
        {{-- Hero --}}
        <div class="relative pt-16 pb-32 flex content-center items-center justify-center min-h-screen hero-background">
            <div class="hero-decorations pointer-events-none" aria-hidden="true">
                <div class="floating-shape shape-1"></div>
                <div class="floating-shape shape-2"></div>
                <div class="floating-shape shape-3"></div>
            </div>
            <div class="absolute inset-0 z-[1] bg-gradient-to-br from-slate-950/75 via-indigo-950/65 to-purple-950/70" aria-hidden="true"></div>

            <div class="container mx-auto px-4 relative z-10">
                <div class="flex flex-wrap items-center justify-center">
                    <div class="w-full max-w-3xl lg:max-w-4xl mx-auto px-4 text-center lg:text-left">
                        <div class="lg-pt-32 sm:pt-0 animate-slide-in pt-6">
                            <div class="flex items-center gap-2 mb-4 justify-center lg:justify-start">
                                <x-lucide-sparkles class="h-6 w-6 text-violet-300 animate-pulse-slow" />
                                <span class="text-violet-200 font-semibold">{{ $welcome['heroBadge'] ?? 'Professional CV Builder' }}</span>
                            </div>

                            <h1 @class([
                                'font-bold leading-tight bg-gradient-to-r from-blue-200 via-purple-200 to-pink-200 bg-clip-text text-transparent text-center lg:text-left',
                                'text-4xl md:text-5xl lg:text-6xl' => $heroCompact,
                                'text-5xl md:text-6xl lg:text-7xl' => ! $heroCompact,
                            ])>{{ $heroTitle }}</h1>
                            <p @class([
                                'mt-4 leading-relaxed text-slate-200 text-center lg:text-left',
                                'text-lg md:text-xl' => $heroCompact,
                                'text-xl lg:text-2xl' => ! $heroCompact,
                            ])>{{ $heroSubtitle }}</p>
                            @if (! empty($suite['heroLine']))
                                <p class="mt-3 text-sm sm:text-base text-slate-300/95 text-center lg:text-left max-w-xl mx-auto lg:mx-0">{{ $suite['heroLine'] }}</p>
                            @endif
                            <p class="mt-3 flex flex-wrap items-center justify-center lg:justify-start gap-x-1 gap-y-1 text-sm text-slate-300">
                                <a href="{{ route('landing.cover-letter') }}" class="font-medium text-violet-200 hover:text-white underline-offset-2 hover:underline">{{ $suite['coverLetter']['shortTitle'] ?? 'Cover letters' }}</a><span class="text-slate-500" aria-hidden="true">·</span><a href="{{ route('landing.work-certificate') }}" class="font-medium text-violet-200 hover:text-white underline-offset-2 hover:underline">{{ $suite['workCertificate']['shortTitle'] ?? 'Work certificates' }}</a>
                            </p>
                            <div class="mt-5 inline-flex items-center gap-2 rounded-full bg-white/10 text-violet-100 px-4 py-2 text-sm font-semibold border border-white/20 backdrop-blur-sm mx-auto lg:mx-0">
                                <x-lucide-sparkles class="h-4 w-4 text-violet-300" />
                                {{ $welcome['outcomePromise'] ?? 'Get interview-ready CV in 60s' }}
                            </div>

                            <div class="mt-10 flex flex-wrap gap-4 justify-center lg:justify-start">
                                <a href="{{ route('register') }}" class="cta-primary inline-flex items-center px-6 sm:px-8 py-3 sm:py-4 text-white rounded-xl text-sm sm:text-base md:text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                                    <x-lucide-rocket class="h-4 w-4 sm:h-5 sm:w-5 mr-2" />
                                    {{ $welcome['getStarted'] ?? 'Get Started' }}
                                    <x-lucide-arrow-right class="h-4 w-4 sm:h-5 sm:w-5 ml-2" />
                                </a>
                                <a href="{{ route('resume.start') }}" class="hero-cta-secondary inline-flex items-center px-6 sm:px-8 py-3 sm:py-4 rounded-xl text-sm sm:text-base md:text-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
                                    {{ $welcome['guestStartCta'] ?? 'Start without an account' }}
                                </a>
                            </div>

                            @if (! empty($welcome['freeDescription']))
                                <p class="mt-3 text-sm text-slate-300 text-center lg:text-left">{{ $welcome['freeDescription'] }}</p>
                            @endif

                            <div class="mt-8 flex items-center gap-6 text-sm text-slate-300 mb-8 flex-wrap justify-center lg:justify-start">
                                @foreach ([$trust['free'] ?? 'Free to use', $trust['noCard'] ?? 'No credit card', $trust['instant'] ?? 'Instant download'] as $label)
                                    <div class="flex items-center gap-2">
                                        <x-lucide-check-circle class="h-5 w-5 text-emerald-400" />
                                        <span>{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div
            class="relative bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-20 overflow-hidden"
            x-data="statsCounter(@js(['candidates' => $stats['total_candidates'], 'resumes' => $stats['total_resumes']]))"
        >
            <div class="absolute top-0 left-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>

            <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                @if ($stats['total_candidates'] > 0)
                    <div class="flex items-center justify-center gap-8 sm:gap-12 md:gap-16 lg:gap-20 flex-wrap">
                        <div class="group text-center transform transition-all duration-500 hover:scale-110">
                            <div class="relative inline-flex items-center justify-center mb-6">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl blur-2xl opacity-40 group-hover:opacity-60 group-hover:blur-3xl transition-all duration-500 animate-pulse-slow"></div>
                                <div class="relative w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 bg-gradient-to-br from-blue-500 via-blue-600 to-purple-600 rounded-3xl flex items-center justify-center shadow-2xl group-hover:shadow-blue-500/50 transition-all duration-500 transform group-hover:rotate-6">
                                    <x-lucide-users class="h-10 w-10 sm:h-12 sm:w-12 md:h-14 md:w-14 text-white" />
                                </div>
                            </div>
                            <div class="mb-3 tabular-nums">
                                <span class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-black bg-gradient-to-r from-blue-600 via-blue-700 via-purple-600 to-purple-700 bg-clip-text text-transparent leading-none" x-text="fmt('candidates')">{{ $formatStat($stats['total_candidates']) }}</span>
                            </div>
                            <p class="text-base sm:text-lg md:text-xl text-gray-700 font-bold tracking-wide uppercase">{{ $welcome['stats']['candidates'] ?? 'Candidates' }}</p>
                        </div>

                        @if ($stats['total_resumes'] > 0)
                            <div class="hidden md:block w-1 h-32 bg-gradient-to-b from-transparent via-blue-300 via-purple-300 to-transparent rounded-full"></div>
                            <div class="group text-center transform transition-all duration-500 hover:scale-110">
                                <div class="relative inline-flex items-center justify-center mb-6">
                                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-3xl blur-2xl opacity-40 group-hover:opacity-60 group-hover:blur-3xl transition-all duration-500 animate-pulse-slow"></div>
                                    <div class="relative w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 bg-gradient-to-br from-purple-500 via-pink-500 to-pink-600 rounded-3xl flex items-center justify-center shadow-2xl group-hover:shadow-pink-500/50 transition-all duration-500 transform group-hover:-rotate-6">
                                        <x-lucide-file-text class="h-10 w-10 sm:h-12 sm:w-12 md:h-14 md:w-14 text-white" />
                                    </div>
                                </div>
                                <div class="mb-3 tabular-nums">
                                    <span class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-black bg-gradient-to-r from-purple-600 via-pink-600 via-pink-700 to-pink-800 bg-clip-text text-transparent leading-none" x-text="fmt('resumes')">{{ $formatStat($stats['total_resumes']) }}</span>
                                </div>
                                <p class="text-base sm:text-lg md:text-xl text-gray-700 font-bold tracking-wide uppercase">{{ $welcome['stats']['resumes'] ?? 'Resumes Created' }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Trust / proof strip --}}
        <div class="relative py-14 border-b border-blue-100/70 bg-gradient-to-br from-white via-blue-50/40 to-purple-50/50">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                    <div class="group relative overflow-hidden rounded-2xl border border-blue-100/90 bg-gradient-to-br from-white via-white to-blue-50/60 p-6 text-left shadow-md shadow-blue-500/10 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-500/15">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blue-400/10 blur-2xl transition-opacity group-hover:opacity-100" aria-hidden="true"></div>
                        <div class="relative mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg shadow-blue-500/35 ring-2 ring-white/80 transition-transform duration-300 group-hover:scale-105">
                            <x-lucide-star class="h-6 w-6" stroke-width="2" aria-hidden="true" />
                        </div>
                        <p class="relative text-xs uppercase tracking-[0.2em] text-blue-600 font-semibold mb-2">{{ $trustProof['testimonialsTitle'] ?? 'Testimonials' }}</p>
                        <p class="relative text-sm text-gray-600 leading-relaxed">{{ $trustProof['testimonialsText'] ?? 'Rated highly by candidates and recruiters for speed, clarity, and ease of use.' }}</p>
                    </div>
                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/90 bg-gradient-to-br from-white via-white to-purple-50/60 p-6 text-left shadow-md shadow-purple-500/10 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-purple-200 hover:shadow-lg hover:shadow-purple-500/15">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-purple-400/10 blur-2xl transition-opacity group-hover:opacity-100" aria-hidden="true"></div>
                        <div class="relative mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 text-white shadow-lg shadow-purple-500/35 ring-2 ring-white/80 transition-transform duration-300 group-hover:scale-105">
                            <x-lucide-badge-percent class="h-6 w-6" stroke-width="2" aria-hidden="true" />
                        </div>
                        <p class="relative text-xs uppercase tracking-[0.2em] text-purple-600 font-semibold mb-2">{{ $trustProof['pricingTitle'] ?? 'Transparent pricing' }}</p>
                        <p class="relative text-sm text-gray-600 leading-relaxed">{{ $trustProof['pricingText'] ?? 'Free to start, optional Pro when you want unlimited AI and full ATS insights.' }}</p>
                        <a href="{{ route('pricing') }}" class="relative mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 hover:opacity-85 underline-offset-4 hover:underline transition-opacity">
                            {{ $trustProof['pricingLink'] ?? 'See plans' }}
                            <x-lucide-arrow-right class="h-4 w-4 shrink-0 text-purple-600 opacity-90" aria-hidden="true" />
                        </a>
                    </div>
                    <div class="group relative overflow-hidden rounded-2xl border border-pink-100/90 bg-gradient-to-br from-white via-white to-pink-50/50 p-6 text-left shadow-md shadow-pink-500/10 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-pink-200 hover:shadow-lg hover:shadow-pink-500/15">
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-pink-400/10 blur-2xl transition-opacity group-hover:opacity-100" aria-hidden="true"></div>
                        <div class="relative mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-pink-400 to-rose-500 text-white shadow-lg shadow-pink-500/35 ring-2 ring-white/80 transition-transform duration-300 group-hover:scale-105">
                            <x-lucide-lock class="h-6 w-6" stroke-width="2" aria-hidden="true" />
                        </div>
                        <p class="relative text-xs uppercase tracking-[0.2em] text-pink-600 font-semibold mb-2">{{ $trustProof['privacyTitle'] ?? 'Privacy first' }}</p>
                        <p class="relative text-sm text-gray-600 leading-relaxed">{{ $trustProof['privacyText'] ?? 'You control what is public. Private resumes stay private unless you choose to share.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Templates --}}
        <div class="relative bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-20">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">{{ $tplStrings['title'] ?? 'Professional Templates' }}</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ $tplStrings['subtitle'] ?? 'Choose from our collection of professionally designed resume templates' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
                    @foreach ($templates as $template)
                        <x-template-card :template="$template" />
                    @endforeach
                </div>

                <div class="text-center mt-12">
                    <p class="text-gray-600 mb-6">{{ $tplStrings['more'] ?? 'And many more templates available' }}</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-full font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                        {{ $tplStrings['cta'] ?? 'Choose Your Template' }}
                        <x-lucide-arrow-right class="h-5 w-5" />
                    </a>
                </div>
            </div>
        </div>

        @if ($showWalkthrough)
            <div class="relative py-20 border-y border-blue-100/80 bg-gradient-to-b from-white via-blue-50/25 to-purple-50/30">
                <div class="container mx-auto px-4">
                    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-blue-600 font-semibold">{{ $walkthrough['badge'] ?? 'Product walkthrough' }}</p>
                            <h2 class="text-4xl md:text-5xl font-bold mt-3 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $walkthrough['title'] ?? 'See how your CV improves in minutes' }}</h2>
                            <p class="text-lg text-gray-600 mt-4 leading-relaxed">{{ $walkthrough['subtitle'] ?? 'Watch the exact flow: choose a template, tailor with AI, check ATS feedback, and export with one click.' }}</p>
                            <div class="mt-6 flex flex-wrap gap-4">
                                <a href="{{ route('resume.start') }}" class="cta-primary inline-flex items-center gap-2 rounded-xl px-6 py-3 font-semibold text-white shadow-lg shadow-purple-500/25 hover:shadow-xl transition">
                                    <x-lucide-play-circle class="h-5 w-5" />
                                    {{ $walkthrough['cta'] ?? 'Try the flow now' }}
                                </a>
                            </div>
                        </div>
                        <div class="rounded-2xl border-2 border-blue-100 overflow-hidden shadow-xl shadow-blue-500/15 ring-1 ring-purple-500/10 bg-gray-900">
                            <div class="aspect-video">
                                @if ($walkthroughVideo)
                                    <iframe class="w-full h-full" src="{{ $walkthroughVideo }}" title="{{ $walkthrough['videoTitle'] ?? 'HResume product walkthrough' }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                @else
                                    <div class="walkthrough-video-placeholder relative w-full h-full min-h-[12rem] flex flex-col items-center justify-center text-center px-6">
                                        <div class="absolute inset-0 bg-white/10 backdrop-blur-[2px]" aria-hidden="true"></div>
                                        <x-lucide-play-circle class="relative h-14 w-14 text-white/90 drop-shadow-md mb-3" />
                                        <p class="relative text-white text-sm max-w-sm font-medium drop-shadow-sm leading-relaxed">{{ $walkthrough['videoPlaceholder'] ?? 'Product walkthrough video is coming soon.' }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Before / After --}}
        <div
            x-data="transformationDemo({{ count($examples) }})"
            class="transformation-section relative bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-20"
            :class="visible ? 'transformation-section--visible' : ''"
        >
            <div class="container mx-auto px-4">
                <div class="text-center mb-14 transformation-section__heading">
                    <p class="text-sm uppercase tracking-[0.3em] text-blue-600 font-semibold">{{ $transformation['badge'] ?? 'Real transformations' }}</p>
                    <h2 class="text-4xl md:text-5xl font-bold mt-3 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $transformation['title'] ?? 'Before and after examples' }}</h2>
                    <p class="text-lg text-gray-600 mt-4 max-w-3xl mx-auto leading-relaxed">{{ $transformation['subtitle'] ?? 'Users keep control while AI sharpens impact and ATS coverage.' }}</p>
                </div>
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($examples as $idx => $example)
                        <div class="transformation-section__card transformation-section__card--{{ $idx + 1 }} bg-white rounded-2xl border border-blue-100 shadow-lg shadow-blue-500/5 p-6">
                            <p class="text-xs uppercase tracking-[0.2em] text-purple-600 font-semibold mb-4">{{ $example['role'] ?? 'Candidate example' }}</p>

                            <div class="space-y-4" x-show="phaseOf({{ $idx }}) === 'idle'">
                                <p class="text-xs text-gray-500 leading-relaxed">{{ $transformation['enhanceHint'] ?? 'Click the button to simulate AI rewriting this line of text.' }}</p>
                                <div class="rounded-xl border border-gray-200 bg-gray-50/90 p-4 text-left shadow-inner">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-2">{{ $transformation['originalLabel'] ?? 'Original text' }}</p>
                                    <p class="text-sm text-gray-800 leading-relaxed">{{ $example['before'] ?? '' }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="enhance({{ $idx }})"
                                    :disabled="enhancingIdx !== null"
                                    class="transformation-enhance-btn inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-500/30 transition-transform hover:scale-[1.02] hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-80"
                                    :class="enhancingIdx === {{ $idx }} ? 'transformation-enhance-btn--clicking' : ''"
                                >
                                    <x-lucide-sparkles class="h-5 w-5 shrink-0" aria-hidden="true" />
                                    {{ $transformation['enhanceWithAi'] ?? 'Enhance with AI' }}
                                </button>
                            </div>

                            <template x-if="phaseOf({{ $idx }}) === 'enhancing'">
                                <div class="transformation-text-snippet transformation-text-snippet--enhancing relative overflow-hidden rounded-xl border border-purple-200/90 bg-gradient-to-br from-white to-purple-50/70 p-4 text-left shadow-md ring-2 ring-purple-300/30">
                                    <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-xl">
                                        <div class="transformation-shimmer-bar absolute inset-y-0 w-2/5 bg-gradient-to-r from-transparent via-white/80 to-transparent opacity-90"></div>
                                    </div>
                                    <p class="relative flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-purple-600 mb-2">
                                        <x-lucide-sparkles class="h-3.5 w-3.5 animate-pulse" aria-hidden="true" />
                                        {{ $transformation['enhancingLabel'] ?? 'Enhancing with AI…' }}
                                    </p>
                                    <p class="relative text-sm text-gray-800 leading-relaxed blur-[0.3px]">{{ $example['before'] ?? '' }}</p>
                                </div>
                            </template>

                            <template x-if="phaseOf({{ $idx }}) === 'done'">
                                <div class="transformation-text-snippet transformation-text-snippet--result transformation-enhanced-enter rounded-xl border border-purple-200 bg-purple-50/90 p-4 text-left shadow-md ring-1 ring-purple-200/50">
                                    <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider text-purple-700 mb-2">
                                        <x-lucide-sparkles class="h-3.5 w-3.5 text-purple-600" aria-hidden="true" />
                                        {{ $transformation['enhancedLabel'] ?? 'Enhanced text' }}
                                    </p>
                                    <p class="text-sm text-purple-950/95 leading-relaxed">{{ $example['after'] ?? '' }}</p>
                                </div>
                            </template>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Application suite --}}
        <div class="relative bg-white py-16 sm:py-20">
            <div class="container mx-auto px-4">
                <x-application-suite-section />
            </div>
        </div>

        {{-- Features --}}
        <div class="relative bg-white py-20 pt-4">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <div class="mb-4 flex justify-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-purple-200 bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.15em] text-purple-700 shadow-sm">
                            <x-lucide-sparkles class="h-4 w-4 text-purple-600" aria-hidden="true" />
                            {{ $wf['aiBadge'] ?? 'AI-assisted' }}
                        </span>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">{{ $wf['title'] ?? '' }}</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed">{{ $wf['subtitle'] ?? 'Everything you need to create a professional resume that stands out' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($featureCards as $item)
                        @php $highlight = $item['highlight'] ?? false; @endphp
                        <div @class([
                            'relative overflow-hidden rounded-2xl p-8 shadow-lg transition-all duration-300 transform hover:-translate-y-2 border group',
                            'border-purple-200/90 bg-gradient-to-br from-white via-purple-50/50 to-fuchsia-50/40 shadow-purple-500/15 ring-1 ring-purple-100 hover:shadow-xl hover:shadow-purple-500/20' => $highlight,
                            'border-gray-100 bg-white hover:shadow-2xl' => ! $highlight,
                        ])>
                            @if ($highlight)
                                <span class="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-violet-600 to-fuchsia-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-md">
                                    <x-lucide-sparkles class="h-3 w-3" aria-hidden="true" />
                                    AI
                                </span>
                            @endif
                            <div @class([
                                'mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-lg transition-transform duration-300 group-hover:scale-110',
                                $item['grad'],
                                'ring-2 ring-white/70' => $highlight,
                            ])>
                                <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-7 w-7" aria-hidden="true" />
                            </div>
                            <h3 @class(['text-2xl font-bold mb-3 text-gray-900', 'pr-16 md:pr-20' => $highlight])>{{ $item['title'] }}</h3>
                            <p class="text-gray-600 leading-relaxed">{{ $item['desc'] }}</p>
                            @if (! empty($item['ai']))
                                <div class="mt-5 flex gap-2.5 rounded-xl border border-purple-100/90 bg-gradient-to-br from-purple-50/80 to-white px-3.5 py-3 text-left shadow-sm">
                                    <x-lucide-sparkles class="h-4 w-4 shrink-0 text-purple-600 mt-0.5" aria-hidden="true" />
                                    <p class="text-xs text-purple-950/90 leading-relaxed">{{ $item['ai'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <x-reviews-carousel :reviews="$reviews" />

                <div class="mt-20 text-center">
                    <div class="inline-block bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-1 rounded-2xl">
                        <div class="bg-white rounded-xl px-12 py-8">
                            <h3 class="text-3xl font-bold text-gray-900 mb-4">{{ $finalCta['title'] ?? 'Ready to create your professional resume?' }}</h3>
                            <p class="text-xl text-gray-600 mb-2">{{ $finalCta['subtitle'] ?? 'Join thousands of professionals who trust our platform' }}</p>
                            @if (! empty($finalCta['freeNote']))
                                <p class="text-base text-gray-500 mb-6">{{ $finalCta['freeNote'] }}</p>
                            @endif
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                <a href="{{ route('register') }}" class="cta-primary inline-flex items-center px-6 sm:px-8 md:px-10 py-3 sm:py-4 text-white rounded-xl text-sm sm:text-base md:text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                                    <x-lucide-rocket class="h-4 w-4 sm:h-5 sm:w-5 mr-2" />
                                    {{ $finalCta['candidate'] ?? 'Candidate signup' }}
                                    <x-lucide-arrow-right class="h-4 w-4 sm:h-5 sm:w-5 ml-2" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
