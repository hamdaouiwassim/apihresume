{{-- Port of pages/PrivacyPolicy.jsx --}}
@php $today = app()->getLocale() === 'fr' ? now()->format('d/m/Y') : now()->format('n/j/Y'); @endphp
<x-layouts.guest
    :title="t('privacy.title', [], 'Privacy Policy').' | HResume'"
    :description="t('privacy.introduction.content', [], 'At HResume, we are committed to protecting your privacy and ensuring the security of your personal information.')"
    canonical="/privacy"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-shield-check class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ t('privacy.title', [], 'Privacy Policy') }}</h1>
                <p class="text-lg text-gray-600">{{ t('privacy.lastUpdated', [], 'Last updated: ') }} {{ $today }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 space-y-8">
                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-file-text class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.introduction.title', [], 'Introduction') }}</h2>
                            <p class="text-gray-700 leading-relaxed">{{ t('privacy.introduction.content', [], 'At HResume, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our resume builder service.') }}</p>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-database class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.informationWeCollect.title', [], 'Information We Collect') }}</h2>
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ t('privacy.informationWeCollect.personalData.title', [], 'Personal Information') }}</h3>
                                    <p class="text-gray-700 leading-relaxed">{{ t('privacy.informationWeCollect.personalData.content', [], 'We collect information that you provide directly to us, including your name, email address, phone number, and any other information you choose to include in your resume.') }}</p>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ t('privacy.informationWeCollect.usageData.title', [], 'Usage Data') }}</h3>
                                    <p class="text-gray-700 leading-relaxed">{{ t('privacy.informationWeCollect.usageData.content', [], 'We automatically collect certain information about your device and how you interact with our service, including IP address, browser type, pages visited, and time spent on pages.') }}</p>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ t('privacy.informationWeCollect.cookies.title', [], 'Cookies and Tracking') }}</h3>
                                    <p class="text-gray-700 leading-relaxed">{{ t('privacy.informationWeCollect.cookies.content', [], 'We use cookies and similar tracking technologies to enhance your experience, analyze usage patterns, and assist in our marketing efforts.') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-eye class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.howWeUse.title', [], 'How We Use Your Information') }}</h2>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 leading-relaxed ml-4">
                                @foreach ((array) t('privacy.howWeUse.points', [], [
                                    'To provide, maintain, and improve our resume builder service',
                                    'To process your requests and transactions',
                                    'To send you technical notices, updates, and support messages',
                                    'To respond to your comments, questions, and requests',
                                    'To monitor and analyze trends, usage, and activities',
                                    'To detect, prevent, and address technical issues and security threats',
                                ]) as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-users class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.dataSharing.title', [], 'Data Sharing and Disclosure') }}</h2>
                            <p class="text-gray-700 leading-relaxed mb-4">{{ t('privacy.dataSharing.intro', [], 'We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:') }}</p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 leading-relaxed ml-4">
                                @foreach ((array) t('privacy.dataSharing.circumstances', [], [
                                    'With your explicit consent',
                                    'To comply with legal obligations or respond to legal requests',
                                    'To protect our rights, privacy, safety, or property',
                                    'With service providers who assist us in operating our platform (under strict confidentiality agreements)',
                                ]) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-lock class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.dataSecurity.title', [], 'Data Security') }}</h2>
                            <p class="text-gray-700 leading-relaxed">{{ t('privacy.dataSecurity.content', [], 'We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet or electronic storage is 100% secure, and we cannot guarantee absolute security.') }}</p>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-start gap-4 mb-4">
                        <x-lucide-globe class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.yourRights.title', [], 'Your Rights') }}</h2>
                            <p class="text-gray-700 leading-relaxed mb-4">{{ t('privacy.yourRights.intro', [], 'You have the right to:') }}</p>
                            <ul class="list-disc list-inside space-y-2 text-gray-700 leading-relaxed ml-4">
                                @foreach ((array) t('privacy.yourRights.rights', [], [
                                    'Access and receive a copy of your personal data',
                                    'Rectify inaccurate or incomplete data',
                                    'Request deletion of your personal data',
                                    'Object to processing of your personal data',
                                    'Request restriction of processing',
                                    'Data portability (receive your data in a structured format)',
                                ]) as $right)
                                    <li>{{ $right }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
                        <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.contact.title', [], 'Contact Us') }}</h2>
                        <p class="text-gray-700 leading-relaxed mb-4">{{ t('privacy.contact.content', [], 'If you have any questions about this Privacy Policy or wish to exercise your rights, please contact us at:') }}</p>
                        <p class="text-gray-700 font-semibold">{{ t('privacy.contact.email', [], 'Email: privacy@hresume.com') }}</p>
                    </div>
                </section>

                <section>
                    <div class="border-t border-gray-200 pt-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('privacy.changes.title', [], 'Changes to This Privacy Policy') }}</h2>
                        <p class="text-gray-700 leading-relaxed">{{ t('privacy.changes.content', [], "We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the 'Last updated' date. You are advised to review this Privacy Policy periodically for any changes.") }}</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts.guest>
