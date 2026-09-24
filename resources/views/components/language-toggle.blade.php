@props(['tone' => 'light', 'toneExpr' => null])
@php
    $current = app()->getLocale();
    $next = $current === 'en' ? 'fr' : 'en';
    $dark = 'border border-white/25 bg-white/10 text-slate-100 hover:bg-white/15';
    $light = 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-offset-2 focus:ring-blue-500';
@endphp
<a
    href="{{ route('locale.switch', $next) }}"
    rel="nofollow"
    @if ($toneExpr)
        x-effect="swap($el, {{ $toneExpr }}, @js($dark), @js($light))"
    @endif
    {{ $attributes->class([
        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-violet-400/50 transition-colors',
        $dark => $tone === 'dark',
        $light => $tone !== 'dark',
    ]) }}
 title="{{ $next === 'fr' ? 'Français' : 'English' }}"
>
    <x-flag :country="$next" />
    <span>{{ strtoupper($next) }}</span>
</a>
