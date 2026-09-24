@props([
    'value',            {{-- JS expression in the parent scope, e.g. "form.message" --}}
    'context' => 'resume',
    'upgradePath' => '/pricing',
    'disabled' => 'false', {{-- JS expression --}}
])
{{-- Port of components/EnhanceTextareaButton.jsx --}}
@php $fr = app()->getLocale() === 'fr'; @endphp
<div
    x-data="enhanceButton({ value: () => {{ $value }}, apply: (v) => { {{ $value }} = v }, context: @js($context), upgradePath: @js($upgradePath), disabled: () => {{ $disabled }} })"
    {{ $attributes->class('flex flex-col gap-2') }}
>
    <x-ai-token-credits compact class="mb-1 self-start" />
    <div class="flex items-center gap-2 flex-wrap">
        <button
            type="button"
            @click="enhance()"
            :disabled="isDisabled"
            :title="!unlimited && !canEnhance ? proTooltip : null"
            class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold border border-violet-300 bg-violet-100 text-violet-800 shadow-[0_0_0_0_rgba(139,92,246,0)] hover:bg-violet-200 hover:shadow-[0_0_22px_rgba(139,92,246,0.45)] focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-1 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <x-lucide-loader-2 class="h-3.5 w-3.5 animate-spin" x-show="enhancing" x-cloak />
            <x-lucide-wand-2 class="h-3.5 w-3.5" x-show="!enhancing" />
            <span x-text="enhancing ? @js($fr ? 'Amelioration...' : 'Enhancing...') : @js($fr ? 'Ameliorer avec IA' : 'Enhance with IA')">{{ $fr ? 'Ameliorer avec IA' : 'Enhance with IA' }}</span>
        </button>
        <template x-if="showQuotaLine">
            <span class="text-[10px] font-medium text-slate-500" x-text="@js($fr ? 'Améliorations' : 'Enhance') + ': ' + (user.ai_quota.enhance?.remaining ?? 0) + '/' + (user.ai_quota.enhance?.limit ?? '—')"></span>
        </template>
    </div>

    <template x-if="enhancing">
        <x-ai-loading-skeleton :rows="5" class="mt-1" />
    </template>

    <template x-if="enhancedText">
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">{{ $fr ? 'Apercu IA' : 'AI Enhancement Preview' }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-xs font-semibold text-slate-500 mb-2">{{ $fr ? 'Avant' : 'Before' }}</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap" x-text="originalText"></p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <p class="text-xs font-semibold text-emerald-700 mb-2">{{ $fr ? 'Apres' : 'After' }}</p>
                    <p class="text-sm text-emerald-900 whitespace-pre-wrap" x-text="enhancedText"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="dismiss()" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <x-lucide-rotate-ccw class="h-3.5 w-3.5" />
                    {{ $fr ? 'Ignorer' : 'Dismiss' }}
                </button>
                <button type="button" @click="applyEnhancement()" class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-200">
                    <x-lucide-check class="h-3.5 w-3.5" />
                    {{ $fr ? 'Appliquer' : 'Apply enhancement' }}
                </button>
            </div>
        </div>
    </template>
</div>
