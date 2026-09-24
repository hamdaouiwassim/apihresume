@props(['country'])
{{-- Inline SVG flags: emoji flags are not rendered on Windows (they show as "FR" / "GB" letters). --}}
@if ($country === 'fr')
    <svg {{ $attributes->merge(['class' => 'h-3.5 w-5 rounded-[2px] shadow-sm ring-1 ring-black/10']) }} viewBox="0 0 3 2" aria-hidden="true" preserveAspectRatio="none">
        <rect width="1" height="2" x="0" fill="#0055A4" />
        <rect width="1" height="2" x="1" fill="#FFFFFF" />
        <rect width="1" height="2" x="2" fill="#EF4135" />
    </svg>
@else
    @php $clipId = 'flag-gb-'.\Illuminate\Support\Str::random(6); @endphp
    <svg {{ $attributes->merge(['class' => 'h-3.5 w-5 rounded-[2px] shadow-sm ring-1 ring-black/10']) }} viewBox="0 0 60 30" aria-hidden="true" preserveAspectRatio="none">
        <clipPath id="{{ $clipId }}"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" /></clipPath>
        <path d="M0,0 v30 h60 v-30 z" fill="#012169" />
        <path d="M0,0 L60,30 M60,0 L0,30" stroke="#FFFFFF" stroke-width="6" />
        <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#{{ $clipId }})" stroke="#C8102E" stroke-width="4" />
        <path d="M30,0 v30 M0,15 h60" stroke="#FFFFFF" stroke-width="10" />
        <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6" />
    </svg>
@endif
