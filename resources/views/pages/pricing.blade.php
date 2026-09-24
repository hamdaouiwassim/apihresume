{{-- Port of pages/Pricing.jsx (prices resolved server-side, no loading spinner) --}}
@php
    $user = auth()->user();
    $pricing = (array) t('pricing', [], []);
    $freePlan = (array) ($pricing['freePlan'] ?? $pricing['plan'] ?? []);
    $proPlan = (array) ($pricing['proPlan'] ?? []);
    $regionNote = $region['isTunisia'] ? ($pricing['regionNoteTunisia'] ?? null) : ($pricing['regionNoteInternational'] ?? null);
    $checkoutStrings = [
        'tunisiaUpgradeNote' => $proPlan['tunisiaUpgradeNote'] ?? 'Online checkout is for international customers. Contact us for Pro in Tunisia.',
        'checkoutError' => $proPlan['checkoutError'] ?? 'Could not start checkout.',
        'billingUnavailable' => $proPlan['billingUnavailable'] ?? null,
        'alreadyPro' => $proPlan['alreadyPro'] ?? null,
    ];
    $loginNext = route('login').'?next='.rawurlencode('/pricing');
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'HResume Pro',
        'description' => $proPlan['description'] ?? 'Unlock unlimited resumes and AI for an active job search.',
        'offers' => [
            ['@type' => 'Offer', 'name' => $freePlan['name'] ?? 'HResume Free', 'price' => '0', 'priceCurrency' => $region['currency']],
            ['@type' => 'Offer', 'name' => $proPlan['name'] ?? 'HResume Pro', 'price' => (string) $region['proAmount'], 'priceCurrency' => $region['currency']],
        ],
    ];
@endphp
<x-layouts.guest
    :title="($pricing['title'] ?? 'Transparent Pricing').' | HResume'"
    :description="$pricing['subtitle'] ?? 'Choose the plan that matches your goals. Upgrade to Pro to unlock AI enhancement features.'"
    canonical="/pricing"
    :json-ld="$jsonLd"
>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 py-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center space-y-6">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm">
                    <x-lucide-badge-dollar-sign class="h-4 w-4" />
                    {{ $pricing['badge'] ?? 'Simple Plans' }}
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-900">{{ $pricing['title'] ?? 'Transparent Pricing' }}</h1>
                <p class="text-lg md:text-xl text-slate-600 max-w-3xl mx-auto">{{ $pricing['subtitle'] ?? 'Choose the plan that matches your goals. Upgrade to Pro to unlock AI enhancement features.' }}</p>
                @if ($regionNote)
                    <p class="text-sm text-slate-500 max-w-xl mx-auto">{{ $regionNote }}</p>
                @endif
                @if ($region['isTunisia'])
                    <p class="text-sm text-amber-700 max-w-xl mx-auto">{{ $proPlan['tunisiaUpgradeNote'] ?? 'Pro checkout online is for international customers. Tunisia: contact support.' }}</p>
                @endif
                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-3 rounded-xl text-white font-semibold bg-gradient-to-r from-blue-500 to-purple-600 shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition">
                        {{ $pricing['heroCta'] ?? 'Create Free Account' }}
                    </a>
                @endguest
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
                    <div class="flex flex-col gap-5 mb-8">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-blue-500 mb-3">{{ $freePlan['name'] ?? 'HResume Free' }}</p>
                            <div class="flex items-baseline gap-2 min-h-[3.5rem]">
                                <span class="text-5xl font-bold text-slate-900">{{ $region['freePrice'] }}</span>
                                <span class="text-slate-500 font-medium">{{ $freePlan['per'] ?? 'per month' }}</span>
                            </div>
                            <p class="text-slate-600 mt-3">{{ $freePlan['description'] ?? 'Build one resume and try AI with monthly limits.' }}</p>
                        </div>
                        <a href="{{ $user ? route('resume.create') : route('resume.start') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-blue-600 border border-blue-200 hover:border-blue-400 hover:bg-blue-50 transition">
                            {{ $freePlan['button'] ?? 'Start for Free' }}
                        </a>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        @foreach ((array) ($freePlan['features'] ?? []) as $feature)
                            <div class="flex items-start gap-3 p-4 rounded-2xl border border-slate-100 bg-slate-50">
                                <x-lucide-check-circle-2 class="h-5 w-5 text-emerald-500 flex-shrink-0 mt-0.5" />
                                <p class="text-slate-700">{{ $feature }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-600 rounded-3xl shadow-2xl p-8 text-white border border-indigo-400">
                    <div class="flex flex-col gap-5 mb-8">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-blue-100 mb-3">{{ $proPlan['name'] ?? 'HResume Pro' }}</p>
                            <div class="flex items-baseline gap-2 min-h-[3.5rem]">
                                <span class="text-5xl font-bold">{{ $region['proPrice'] }}</span>
                                <span class="text-blue-100 font-medium">{{ $proPlan['per'] ?? 'per month' }}</span>
                            </div>
                            <p class="text-blue-50 mt-3">{{ $proPlan['description'] ?? 'Unlock unlimited resumes and AI for an active job search.' }}</p>
                        </div>

                        @if ($user?->is_pro)
                            <span class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-indigo-700 bg-white/90 cursor-default">
                                <x-lucide-crown class="h-4 w-4 mr-2" />
                                {{ $proPlan['alreadyPro'] ?? 'You are on Pro' }}
                            </span>
                        @elseif (! $user)
                            <a href="{{ $loginNext }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-indigo-700 bg-white hover:bg-slate-100 transition">
                                {{ $proPlan['loginToUpgrade'] ?? 'Sign in to upgrade' }}
                            </a>
                        @elseif ($region['isTunisia'])
                            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-indigo-700 bg-white hover:bg-slate-100 transition">
                                <x-lucide-mail class="h-4 w-4 mr-2" />
                                {{ $proPlan['tunisiaContactCta'] ?? 'Contact us for Pro' }}
                            </a>
                        @else
                            <button
                                type="button"
                                x-data="proCheckout(@js(['region' => $region['region'], 'isTunisia' => $region['isTunisia'], 'strings' => $checkoutStrings]))"
                                @click="upgrade()"
                                :disabled="loading"
                                class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-semibold text-indigo-700 bg-white hover:bg-slate-100 transition disabled:opacity-70"
                            >
                                <x-lucide-loader-2 class="h-4 w-4 mr-2 animate-spin" x-show="loading" x-cloak />
                                <span x-text="loading ? @js($proPlan['buttonLoading'] ?? 'Redirecting to checkout…') : @js($proPlan['button'] ?? 'Upgrade to Pro')">{{ $proPlan['button'] ?? 'Upgrade to Pro' }}</span>
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        @foreach ((array) ($proPlan['features'] ?? []) as $feature)
                            <div class="flex items-start gap-3 p-4 rounded-2xl border border-white/20 bg-white/10">
                                <x-lucide-check-circle-2 class="h-5 w-5 text-emerald-300 flex-shrink-0 mt-0.5" />
                                <p class="text-white">{{ $feature }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-100 rounded-3xl p-8 flex flex-col md:flex-row gap-6 items-start">
                <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white text-blue-600 flex items-center justify-center shadow-md">
                    <x-lucide-shield class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-3">{{ $pricing['note']['title'] ?? 'Why Pro?' }}</h2>
                    <p class="text-slate-700">{{ $pricing['note']['description'] ?? 'Free is built for one strong CV and a monthly taste of AI. Pro is for active job searches.' }}</p>
                    <p class="text-sm text-slate-600 mt-3">
                        <a href="{{ route('refund') }}" class="text-blue-600 font-semibold hover:underline">{{ $pricing['note']['refundLink'] ?? 'Refund policy' }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
