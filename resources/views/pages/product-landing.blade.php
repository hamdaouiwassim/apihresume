{{-- Port of pages/ProductMarketingPage.jsx (used by /cover-letter-builder and /work-certificate) --}}
@php
    $configs = [
        'coverLetter' => ['canonical' => '/cover-letter-builder', 'registerNext' => '/cover-letters', 'otherHref' => '/work-certificate', 'otherKey' => 'workCertificate'],
        'workCertificate' => ['canonical' => '/work-certificate', 'registerNext' => '/work-certificates', 'otherHref' => '/cover-letter-builder', 'otherKey' => 'coverLetter'],
    ];
    $config = $configs[$product];
    $content = (array) t('productLanding.'.$product, [], []);
    $other = (array) t('productLanding.'.$config['otherKey'], [], []);
    $common = (array) t('productLanding.common', [], []);
    $registerUrl = '/register?next='.rawurlencode($config['registerNext']);
@endphp
<x-layouts.guest
    :title="$content['metaTitle'] ?? 'HResume'"
    :description="$content['metaDescription'] ?? ''"
    :canonical="$config['canonical']"
>
    <article class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/50 to-purple-50/60">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 space-y-12">
            <header class="text-center space-y-5">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-purple-600">{{ $content['badge'] ?? 'HResume' }}</p>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 leading-tight">{{ $content['title'] ?? '' }}</h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">{{ $content['subtitle'] ?? '' }}</p>
                <p class="text-sm text-slate-500 max-w-xl mx-auto">{{ $content['accountNote'] ?? '' }}</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                    <a href="{{ url($registerUrl) }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg hover:from-blue-700 hover:to-purple-700 transition">
                        {{ $content['primaryCta'] ?? $common['primaryCta'] ?? 'Create free account' }}
                        <x-lucide-arrow-right class="h-4 w-4" />
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-xl font-semibold text-slate-700 border border-slate-200 bg-white hover:bg-slate-50 transition">
                        {{ $content['secondaryCta'] ?? $common['secondaryCta'] ?? 'Log in' }}
                    </a>
                </div>
            </header>

            <section class="bg-white/80 backdrop-blur-sm rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900 mb-4">{{ $content['featuresTitle'] ?? 'What you get' }}</h2>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ((array) ($content['bullets'] ?? []) as $text)
                        <li class="flex gap-2.5 text-sm text-slate-700">
                            <x-lucide-check-circle-2 class="h-5 w-5 text-emerald-500 shrink-0" aria-hidden="true" />
                            <span>{{ $text }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @foreach ((array) ($content['bodyParagraphs'] ?? []) as $paragraph)
                <p class="text-slate-600 leading-relaxed text-center max-w-2xl mx-auto">{{ $paragraph }}</p>
            @endforeach

            <section class="text-center rounded-2xl border border-dashed border-slate-300 bg-white/60 p-6">
                <p class="text-sm text-slate-600 mb-3">{{ $common['alsoTry'] ?? '' }}</p>
                <a href="{{ localized_url($config['otherHref']) }}" class="font-semibold text-blue-600 hover:text-purple-700 hover:underline">{{ $other['shortTitle'] ?? $config['otherKey'] }}</a><span class="text-slate-400 mx-2">·</span><a href="{{ localized_url('/') }}" class="font-semibold text-blue-600 hover:text-purple-700 hover:underline">{{ $common['backHome'] ?? 'Back to homepage' }}</a>
            </section>

            <x-application-suite-section />
        </div>
    </article>
</x-layouts.guest>
