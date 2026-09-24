{{-- Port of pages/RefundPolicy.jsx --}}
<x-layouts.guest
    :title="t('refund.title', [], 'Refund Policy').' | HResume'"
    :description="t('refund.introduction.content', [], 'This Refund Policy explains how refunds work for HResume Pro and related paid services.')"
    canonical="/refund"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-rotate-ccw class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ t('refund.title', [], 'Refund Policy') }}</h1>
                <p class="text-lg text-gray-600">{{ t('refund.lastUpdated', [], 'Last updated:') }} {{ t('refund.lastUpdatedDate', [], 'May 2026') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 space-y-10">
                <x-legal-section icon="file-text">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('refund.introduction.title', [], 'Introduction') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.introduction.content', [], 'This Refund Policy explains how refunds work for HResume Pro and related paid services. The free plan does not involve charges and is not eligible for refunds.') }}</p>
                </x-legal-section>

                <x-legal-section icon="credit-card">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('refund.eligibility.title', [], 'Refund eligibility') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.eligibility.intro', [], 'We want you to be satisfied with HResume Pro. Refunds may be available under the conditions below.') }}</p>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        @foreach ((array) t('refund.eligibility.points', [], [
                            'First-time Pro subscription: you may request a full refund within 14 days of your initial payment if you have not substantially used Pro features (e.g. unlimited AI beyond a reasonable trial).',
                            'Billing errors or duplicate charges: contact us within 30 days and we will correct or refund the charge.',
                            'Renewals: subscription renewals are generally non-refundable except where required by law or at our discretion for exceptional cases.',
                        ]) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </x-legal-section>

                <x-legal-section icon="clock">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('refund.howToRequest.title', [], 'How to request a refund') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.howToRequest.content', [], 'To request a refund, email us from the address linked to your HResume account.') }}</p>
                    <ul class="list-decimal list-inside space-y-2 text-gray-700">
                        @foreach ((array) t('refund.howToRequest.steps', [], [
                            'Send an email to contact@hresume.pro with the subject "Refund request".',
                            'Include your account email, payment date, and reason for the request.',
                            'We will review your request and respond within 5–10 business days.',
                        ]) as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ul>
                </x-legal-section>

                <x-legal-section icon="ban">
                    <h2 class="text-2xl font-bold text-gray-900">{{ t('refund.nonRefundable.title', [], 'Non-refundable situations') }}</h2>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        @foreach ((array) t('refund.nonRefundable.points', [], [
                            'Free plan usage (no payment was made).',
                            'Pro access granted manually by our team without a charge.',
                            'Requests made more than 14 days after the initial Pro payment (except billing errors).',
                            'Account termination due to violation of our terms or abuse of the service.',
                        ]) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                </x-legal-section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('refund.cancellations.title', [], 'Cancellations') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.cancellations.content', [], 'You may cancel Pro at any time. Cancellation stops future renewals; you keep Pro until the end of the current billing period. Canceling does not automatically issue a refund for the current period unless eligible under this policy.') }}</p>
                </section>

                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('refund.paddle.title', [], 'International payments (Paddle)') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.paddle.content', [], 'International Pro subscriptions are processed by Paddle as merchant of record. Refunds for eligible requests are issued through Paddle to your original payment method. Processing times depend on your bank or card issuer (typically 5–14 business days after approval).') }}</p>
                </section>

                <section class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
                    <div class="flex items-start gap-3">
                        <x-lucide-mail class="h-6 w-6 text-blue-600 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('refund.contact.title', [], 'Contact us') }}</h2>
                            <p class="text-gray-700 leading-relaxed mb-4">{{ t('refund.contact.content', [], 'Questions about refunds or billing? We are happy to help.') }}</p>
                            <p class="text-gray-700 font-semibold">
                                <a href="mailto:contact@hresume.pro" class="text-blue-600 hover:underline">contact@hresume.pro</a>
                            </p>
                            <p class="mt-4 text-sm text-gray-600">
                                <a href="{{ route('contact') }}" class="text-blue-600 hover:underline font-medium">{{ t('refund.contact.formLink', [], 'Contact form') }}</a>
                                ·
                                <a href="{{ route('pricing') }}" class="text-blue-600 hover:underline font-medium">{{ t('refund.contact.pricingLink', [], 'Pricing') }}</a>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="border-t border-gray-200 pt-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('refund.changes.title', [], 'Changes to this policy') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ t('refund.changes.content', [], 'We may update this Refund Policy from time to time. Changes are effective when posted on this page with an updated date.') }}</p>
                </section>
            </div>
        </div>
    </div>
</x-layouts.guest>
