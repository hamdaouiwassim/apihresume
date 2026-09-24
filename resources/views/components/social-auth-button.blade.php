@props(['provider', 'title', 'subtitle', 'fallback', 'border' => 'border-slate-200'])
{{-- LinkedIn / Google buttons from login.jsx and register.jsx --}}
@php $loading = $provider === 'google' ? 'googleLoading' : 'linkedinLoading'; @endphp
<button
    type="button"
    @click="social(@js($provider), @js($fallback))"
    :disabled="socialBusy"
    {{ $attributes->class("w-full flex items-center gap-3 rounded-2xl border {$border} bg-white py-3.5 px-4 shadow-sm hover:-translate-y-0.5 hover:shadow-xl transition-all duration-200 disabled:opacity-60") }}
>
    @if ($provider === 'linkedin')
        <span class="h-10 w-10 rounded-xl bg-[#0A66C2]/10 border border-[#0A66C2]/20 flex items-center justify-center">
            <x-lucide-loader-2 class="h-5 w-5 animate-spin text-[#0A66C2]" x-show="{{ $loading }}" x-cloak />
            <x-lucide-linkedin class="h-5 w-5 text-[#0A66C2]" x-show="!{{ $loading }}" />
        </span>
    @else
        <span class="h-10 w-10 rounded-xl bg-gradient-to-br from-white via-red-50 to-rose-100 border border-rose-100 flex items-center justify-center">
            <x-lucide-loader-2 class="h-5 w-5 animate-spin text-rose-500" x-show="{{ $loading }}" x-cloak />
            <x-lucide-chrome class="h-5 w-5 text-rose-500" x-show="!{{ $loading }}" />
        </span>
    @endif
    <div class="text-left">
        <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
        <p class="text-xs text-slate-500">{{ $subtitle }}</p>
    </div>
</button>
