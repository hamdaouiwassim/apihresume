{{-- Port of pages/PricingSuccess.jsx --}}
@php $s = (array) t('pricing.success', [], []); @endphp
<x-layouts.guest title="Payment | HResume" robots="noindex, nofollow">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50/40 py-20" x-data="pricingSuccess">
        <div class="max-w-lg mx-auto px-4 text-center space-y-6">
            <div x-show="status === 'pending'" class="space-y-6">
                <x-lucide-loader-2 class="h-12 w-12 animate-spin text-blue-600 mx-auto" />
                <p class="text-slate-600">{{ $s['pending'] ?? 'Confirming your payment…' }}</p>
            </div>
            <div x-show="status === 'success'" x-cloak class="space-y-6">
                <x-lucide-check-circle-2 class="h-16 w-16 text-emerald-500 mx-auto" />
                <h1 class="text-3xl font-bold text-slate-900">{{ $s['title'] ?? 'Welcome to Pro!' }}</h1>
                <p class="text-slate-600">{{ $s['subtitle'] ?? 'Your subscription is active. Unlimited resumes and AI are now unlocked.' }}</p>
                <a href="{{ route('resumes.index') }}" class="inline-flex px-8 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600">{{ $s['goToResumes'] ?? 'Go to my resumes' }}</a>
            </div>
            <div x-show="status === 'error' || status === 'missing_session'" x-cloak class="space-y-6">
                <x-lucide-alert-circle class="h-16 w-16 text-amber-500 mx-auto" />
                <p class="text-slate-700">{{ $s['error'] ?? 'We could not confirm your payment. If you were charged, contact support.' }}</p>
                <a href="{{ route('pricing') }}" class="text-blue-600 font-semibold hover:underline">{{ $s['goToPricing'] ?? 'Back to pricing' }}</a>
            </div>
        </div>
    </div>
</x-layouts.guest>
