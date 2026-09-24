{{-- Port of components/UpgradeProModal.jsx. Open with: $store.upgrade.show('quota' | 'resume_limit' | 'default', '/pricing') --}}
@php $fr = app()->getLocale() === 'fr'; @endphp
<div
    x-data
    x-show="$store.upgrade.open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    role="dialog"
    aria-modal="true"
    @keydown.escape.window="$store.upgrade.open && $store.upgrade.close()"
>
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden" @click.outside="$store.upgrade.close()">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-lucide-crown class="h-5 w-5 text-amber-500" />
                <h3 class="text-lg font-bold text-slate-900">{{ $fr ? 'Passez a Pro' : 'Upgrade to Pro' }}</h3>
            </div>
            <button type="button" @click="$store.upgrade.close()" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Close">
                <x-lucide-x class="h-4 w-4" />
            </button>
        </div>

        <div class="p-5 space-y-4">
            <p class="text-sm text-slate-700" x-text="$store.upgrade.variant === 'quota'
                ? @js($fr ? 'Vous avez utilise toutes vos utilisations IA gratuites ce mois-ci. Passez a Pro pour un acces illimite.' : 'You have used all free AI credits for this month. Upgrade to Pro for unlimited AI and full ATS insights.')
                : ($store.upgrade.variant === 'resume_limit'
                    ? @js($fr ? "L'offre gratuite inclut un seul CV. Passez a Pro pour en creer autant que vous voulez." : 'The free plan includes one resume. Upgrade to Pro to create unlimited resumes.')
                    : @js($fr ? "L'amelioration IA et les outils avances sont inclus dans l'offre Pro." : 'AI enhancement and advanced tools are included on the Pro plan.'))"></p>
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 mb-2">Pro feature</p>
                <div class="flex items-start gap-2 text-sm text-indigo-900">
                    <x-lucide-sparkles class="h-4 w-4 mt-0.5" />
                    <span>{{ $fr ? 'IA illimitee, ATS complet, priorite de traitement' : 'Unlimited AI, full ATS keyword insights, priority processing' }}</span>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="$store.upgrade.close()" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50">
                    {{ $fr ? 'Plus tard' : 'Maybe later' }}
                </button>
                <a
                    :href="$store.upgrade.path"
                    class="px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold hover:from-blue-700 hover:to-indigo-700"
                    x-text="@js($fr ? "Voir l'offre Pro (" : 'View Pro Plan (') + ($store.upgrade.proPrice || '$5') + ')'"
                ></a>
            </div>
        </div>
    </div>
</div>
