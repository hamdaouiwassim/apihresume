{{-- Port of pages/TermsOfService.jsx --}}
@php
    $list = fn (string $key) => (array) t($key, [], []);
@endphp
<x-layouts.guest
    :title="t('terms.title', [], 'Terms and Conditions').' | HResume'"
    :description="t('terms.introduction.content', [], 'Terms and conditions for using HResume.')"
    canonical="/terms"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-scale class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ t('terms.title', [], 'Terms and Conditions') }}</h1>
                <p class="text-lg text-gray-600">{{ t('terms.lastUpdated', [], 'Last updated:') }} {{ t('terms.lastUpdatedDate', [], 'May 2026') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 space-y-10">
                <x-legal-section icon="file-text">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.introduction.title', [], 'Agreement') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.introduction.content', [], '') }}</p>
                </x-legal-section>

                <x-legal-section icon="scale">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.service.title', [], 'The service') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.service.content', [], '') }}</p>
                </x-legal-section>

                <x-legal-section icon="user">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.accounts.title', [], 'Accounts') }}</h2>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        @foreach ($list('terms.accounts.points') as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </x-legal-section>

                <x-legal-section icon="shield">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.acceptableUse.title', [], 'Acceptable use') }}</h2>
                    @if (t('terms.acceptableUse.intro', [], ''))
                        <p class="text-gray-700 leading-relaxed">{{ t('terms.acceptableUse.intro') }}</p>
                    @endif
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        @foreach ($list('terms.acceptableUse.points') as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </x-legal-section>

                <x-legal-section icon="file-text">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.content.title', [], 'Your content') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.content.content', [], '') }}</p>
                </x-legal-section>

                <x-legal-section icon="sparkles">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.ai.title', [], 'AI features') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.ai.content', [], '') }}</p>
                </x-legal-section>

                <x-legal-section icon="credit-card">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.payments.title', [], 'Paid plans') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.payments.content', [], '') }}</p>
                    <p class="text-sm text-gray-600">
                        <a href="{{ route('pricing') }}" class="text-blue-600 hover:underline font-medium">Pricing</a>
                        ·
                        <a href="{{ route('refund') }}" class="text-blue-600 hover:underline font-medium">{{ t('terms.contact.refundLink', [], 'Refund Policy') }}</a>
                    </p>
                </x-legal-section>

                <x-legal-section icon="lock">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.privacy.title', [], 'Privacy') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.privacy.content', [], '') }}</p>
                    <p class="text-sm">
                        <a href="{{ route('privacy') }}" class="text-blue-600 hover:underline font-medium">{{ t('terms.contact.privacyLink', [], 'Privacy Policy') }}</a>
                    </p>
                </x-legal-section>

                <x-legal-section icon="alert-triangle">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.disclaimer.title', [], 'Disclaimer') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.disclaimer.content', [], '') }}</p>
                </x-legal-section>

                <x-legal-section icon="gavel">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('terms.liability.title', [], 'Limitation of liability') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.liability.content', [], '') }}</p>
                </x-legal-section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('terms.termination.title', [], 'Termination') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.termination.content', [], '') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('terms.law.title', [], 'Governing law') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.law.content', [], '') }}</p>
                </section>

                <section class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
                    <div class="flex items-start gap-3">
                        <x-lucide-mail class="h-6 w-6 text-blue-600 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('terms.contact.title', [], 'Contact') }}</h2>
                            <p class="text-gray-700 leading-relaxed mb-4">{{ t('terms.contact.content', [], '') }}</p>
                            <p class="text-gray-700 font-semibold">
                                <a href="mailto:contact@hresume.pro" class="text-blue-600 hover:underline">contact@hresume.pro</a>
                            </p>
                            <p class="mt-4 text-sm text-gray-600">
                                <a href="{{ route('contact') }}" class="text-blue-600 hover:underline font-medium">{{ t('terms.contact.formLink', [], 'Contact form') }}</a>
                                ·
                                <a href="{{ route('privacy') }}" class="text-blue-600 hover:underline font-medium">{{ t('terms.contact.privacyLink', [], 'Privacy') }}</a>
                                ·
                                <a href="{{ route('refund') }}" class="text-blue-600 hover:underline font-medium">{{ t('terms.contact.refundLink', [], 'Refunds') }}</a>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-gray-200 pt-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('terms.changes.title', [], 'Changes') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('terms.changes.content', [], '') }}</p>
                </section>
            </div>
        </div>
    </div>
</x-layouts.guest>
