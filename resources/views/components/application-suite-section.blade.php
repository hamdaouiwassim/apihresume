@props(['variant' => 'light'])
{{-- Port of components/ApplicationSuiteSection.jsx --}}
@php
    $isDark = $variant === 'dark';
    $items = [
        ['key' => 'resume', 'icon' => 'file-text', 'grad' => 'from-blue-500 to-indigo-600', 'href' => '/resume/start'],
        ['key' => 'coverLetter', 'icon' => 'mail', 'grad' => 'from-purple-500 to-fuchsia-600', 'href' => '/cover-letter-builder'],
        ['key' => 'workCertificate', 'icon' => 'scroll-text', 'grad' => 'from-emerald-500 to-teal-600', 'href' => '/work-certificate'],
    ];
    $sectionClass = $isDark ? 'bg-slate-900/40 border-white/10' : 'bg-gradient-to-br from-slate-50 via-blue-50/40 to-purple-50/50 border-slate-200/80';
    $cardClass = $isDark ? 'bg-white/5 border-white/10 hover:bg-white/10' : 'bg-white/90 border-slate-200/80 hover:border-blue-200 hover:shadow-lg';
    $titleClass = $isDark ? 'text-white' : 'text-slate-900';
    $descClass = $isDark ? 'text-slate-300' : 'text-slate-600';
    $badgeClass = $isDark ? 'border-white/20 bg-white/10 text-violet-200' : 'border-purple-200 bg-white text-purple-700';
@endphp
<section class="rounded-3xl border p-6 sm:p-10 {{ $sectionClass }}" aria-labelledby="application-suite-heading">
    <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] {{ $badgeClass }}">
            {{ t('welcome.suite.badge', [], 'Complete your application') }}
        </span>
        <h2 id="application-suite-heading" class="mt-4 text-2xl sm:text-3xl font-bold {{ $titleClass }}">
            {{ t('welcome.suite.title', [], 'More than a resume') }}
        </h2>
        <p class="mt-2 text-sm sm:text-base leading-relaxed {{ $descClass }}">
            {{ t('welcome.suite.subtitle', [], 'Build your CV, cover letter, and employment certificate in one place—free to start.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        @foreach ($items as $item)
            @php $card = (array) t('welcome.suite.'.$item['key'], [], []); @endphp
            <a href="{{ url($item['href']) }}" class="group flex flex-col rounded-2xl border p-5 sm:p-6 transition-all duration-300 {{ $cardClass }}">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $item['grad'] }} text-white shadow-md">
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-6 w-6" aria-hidden="true" />
                </div>
                <h3 class="text-lg font-semibold {{ $titleClass }}">{{ $card['title'] ?? $item['key'] }}</h3>
                <p class="mt-2 text-sm flex-1 leading-relaxed {{ $descClass }}">{{ $card['description'] ?? '' }}</p>
                <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold {{ $isDark ? 'text-violet-300 group-hover:text-white' : 'text-blue-600 group-hover:text-purple-700' }}">
                    {{ $card['cta'] ?? 'Learn more' }}
                    <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                </span>
                @if (! empty($card['note']))
                    <p class="mt-2 text-xs {{ $isDark ? 'text-slate-400' : 'text-slate-500' }}">{{ $card['note'] }}</p>
                @endif
            </a>
        @endforeach
    </div>
</section>
