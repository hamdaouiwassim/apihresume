@props(['compact' => false])
{{-- Port of components/AiTokenCredits.jsx (reactive via Alpine.store('auth')) --}}
@php $fr = app()->getLocale() === 'fr'; @endphp
@if ($compact)
    <span x-data="aiTokenCredits" x-show="!unlimited" x-cloak
        {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums') }}
        :class="exhausted ? 'bg-red-100 text-red-800' : (isPro ? 'bg-amber-100 text-amber-900' : 'bg-violet-100 text-violet-800')"
        :title="{{ $fr ? "remaining + ' crédits IA restants ce mois'" : "remaining + ' AI credits left this month'" }}"
    >
        <x-lucide-sparkles class="h-3 w-3" />
        <span x-text="fmt(remaining) + ' / ' + fmt(total)"></span>
    </span>
@else
    <div x-data="aiTokenCredits" {{ $attributes }}>
        <template x-if="unlimited">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 flex items-center gap-2 text-sm text-emerald-800">
                <x-lucide-infinity class="h-4 w-4 shrink-0" />
                <span class="font-medium">{{ $fr ? 'IA illimitée (admin)' : 'Unlimited AI (admin)' }}</span>
            </div>
        </template>
        <template x-if="!unlimited">
            <div class="rounded-2xl border p-4 sm:p-5 shadow-sm"
                :class="exhausted ? 'border-red-200 bg-red-50/60' : (isPro ? 'border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50/80' : 'border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50/80')">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-3">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm" :class="isPro ? 'text-amber-600' : 'text-violet-600'">
                            <x-lucide-sparkles class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900" x-text="isPro ? @js($fr ? 'Crédits IA (plan Pro)' : 'AI credits (Pro plan)') : @js($fr ? 'Crédits IA (plan gratuit)' : 'AI credits (free plan)')"></h3>
                            <p class="text-xs text-gray-600 mt-0.5">{{ $fr ? 'Renouvelés chaque mois calendaire. Toutes les fonctions IA partagent ce pool.' : 'Reset each calendar month. All AI features share this pool.' }}</p>
                        </div>
                    </div>
                    <template x-if="exhausted && !isPro">
                        <a href="{{ url('/pricing') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-3 py-2 text-xs font-bold text-white shadow-sm hover:opacity-95 shrink-0">
                            <x-lucide-crown class="h-3.5 w-3.5" />
                            {{ $fr ? 'Passer Pro' : 'Upgrade Pro' }}
                        </a>
                    </template>
                    <template x-if="exhausted && isPro">
                        <p class="text-xs font-medium text-red-800 shrink-0 max-w-[200px]">{{ $fr ? 'Quota Pro épuisé pour ce mois. Réessayez le mois prochain ou contactez le support.' : 'Pro quota used for this month. Try again next month or contact support.' }}</p>
                    </template>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-3 text-center">
                    <div class="rounded-xl bg-white/80 px-2 py-2 ring-1" :class="isPro ? 'ring-amber-100' : 'ring-violet-100'">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $fr ? 'Utilisés' : 'Used' }}</p>
                        <p class="text-lg font-bold text-gray-900 tabular-nums" x-text="fmt(used)"></p>
                    </div>
                    <div class="rounded-xl bg-white/80 px-2 py-2 ring-1" :class="isPro ? 'ring-amber-100' : 'ring-violet-100'">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ $fr ? 'Restants' : 'Left' }}</p>
                        <p class="text-lg font-bold tabular-nums" :class="exhausted ? 'text-red-600' : (isPro ? 'text-amber-700' : 'text-violet-700')" x-text="fmt(remaining)"></p>
                    </div>
                    <div class="rounded-xl bg-white/80 px-2 py-2 ring-1" :class="isPro ? 'ring-amber-100' : 'ring-violet-100'">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Total</p>
                        <p class="text-lg font-bold text-gray-900 tabular-nums" x-text="fmt(total)"></p>
                    </div>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/90 ring-1" :class="isPro ? 'ring-amber-100' : 'ring-violet-100'">
                    <div class="h-full rounded-full transition-all duration-500"
                        :class="pct >= 90 ? 'bg-red-500' : (pct >= 70 ? 'bg-amber-500' : (isPro ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-violet-500 to-fuchsia-500'))"
                        :style="'width: ' + Math.min(100, pct) + '%'"></div>
                </div>
                <p class="mt-2 text-xs text-gray-600 tabular-nums" x-text="pct + '% ' + @js($fr ? 'du quota mensuel utilisé' : 'of monthly quota used') + (tokens?.month ? ' · ' + tokens.month : '')"></p>
            </div>
        </template>
    </div>
@endif
