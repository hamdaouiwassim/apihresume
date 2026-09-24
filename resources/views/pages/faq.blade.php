{{-- Port of pages/FAQ.jsx --}}
@php
    $questions = (array) t('faq.questions', [], []);
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($questions)->map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => is_array($item['answer'] ?? null) ? implode(' ', $item['answer']) : ($item['answer'] ?? ''),
            ],
        ])->values()->all(),
    ];
@endphp
<x-layouts.guest
    :title="t('faq.title', [], 'Frequently Asked Questions').' | HResume'"
    :description="t('faq.subtitle', [], 'Find answers to common questions about our resume builder service')"
    canonical="/faq"
    :json-ld="$jsonLd"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-help-circle class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ t('faq.title', [], 'Frequently Asked Questions') }}</h1>
                <p class="text-lg text-gray-600">{{ t('faq.subtitle', [], 'Find answers to common questions about our resume builder service') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden" x-data="{ openIndex: null }">
                @forelse ($questions as $index => $item)
                    <div
                        class="border-b border-gray-200 last:border-b-0 transition-all duration-200"
                        :class="openIndex === {{ $index }} ? 'bg-gray-50' : ''"
                    >
                        <button
                            type="button"
                            @click="openIndex = openIndex === {{ $index }} ? null : {{ $index }}"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset"
                            :aria-expanded="openIndex === {{ $index }}"
                        >
                            <span class="text-lg font-semibold text-gray-900 pr-4">{{ $item['question'] ?? 'Question '.($index + 1) }}</span>
                            <x-lucide-chevron-up class="h-5 w-5 text-blue-600 flex-shrink-0" x-show="openIndex === {{ $index }}" x-cloak />
                            <x-lucide-chevron-down class="h-5 w-5 text-gray-400 flex-shrink-0" x-show="openIndex !== {{ $index }}" />
                        </button>
                        <div
                            class="overflow-hidden transition-all duration-300 ease-in-out max-h-0 opacity-0"
                            x-effect="$swap($el, openIndex === {{ $index }}, 'max-h-[1000px] opacity-100', 'max-h-0 opacity-0')"
                        >
                            <div class="px-6 pb-5 text-gray-700 leading-relaxed">
                                @if (is_array($item['answer'] ?? null))
                                    <div class="space-y-2">
                                        @foreach ($item['answer'] as $paragraph)
                                            <p>{{ $paragraph }}</p>
                                        @endforeach
                                    </div>
                                @else
                                    <p>{{ $item['answer'] ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <p>{{ t('faq.noQuestions', [], 'No questions available at the moment.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ t('faq.contact.title', [], 'Still have questions?') }}</h2>
                <p class="text-gray-700 leading-relaxed mb-4">{{ t('faq.contact.content', [], "If you can't find the answer you're looking for, feel free to reach out to our support team.") }}</p>
                <p class="text-gray-700 font-semibold">{{ t('faq.contact.email', [], 'Email: support@hresume.com') }}</p>
            </div>
        </div>
    </div>
</x-layouts.guest>
