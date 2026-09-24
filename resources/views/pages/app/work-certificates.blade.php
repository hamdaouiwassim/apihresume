{{-- Port of pages/WorkCertificates.jsx --}}
@php
    $s = (array) t('workCertificate', [], []);
    $locale = app()->getLocale() === 'fr' ? 'fr' : 'en';
    $state = [
        'ids' => collect($items)->pluck('id')->map(fn ($id) => (int) $id)->values(),
        'strings' => [
            'confirmTitle' => t('common.confirmDelete', [], 'Delete certificate'),
            'confirmMessage' => $s['messages']['deleteConfirmMessage'] ?? 'Are you sure you want to delete this certificate?',
            'confirmText' => t('common.confirmDelete', [], 'Delete'),
            'cancelText' => t('common.cancel', [], 'Cancel'),
            'deleteSuccess' => $s['messages']['deleteSuccess'] ?? 'Deleted',
            'deleteError' => $s['messages']['deleteError'] ?? 'Failed',
            'pdfError' => $s['messages']['pdfError'] ?? 'Failed to download PDF',
        ],
    ];
@endphp
<x-layouts.app :title="($s['title'] ?? 'Work certificates').' | HResume'">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-slate-50 to-blue-50 py-12" x-data="documentList(@js($state))">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-10 gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2 text-blue-700 font-semibold text-sm">
                        <x-lucide-scroll-text class="h-5 w-5" />
                        {{ $s['title'] ?? '' }}
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-700 via-indigo-600 to-slate-700 bg-clip-text text-transparent">{{ $s['title'] ?? '' }}</h1>
                    <p class="text-gray-600 mt-2 max-w-xl">{{ $s['subtitle'] ?? '' }}</p>
                    <p class="text-sm text-gray-500 mt-1" x-text="count + ' ' + (count === 1 ? @js($s['countOne'] ?? '') : @js($s['countOther'] ?? ''))">{{ count($items) }} {{ count($items) === 1 ? ($s['countOne'] ?? '') : ($s['countOther'] ?? '') }}</p>
                </div>
                <a href="{{ route('work-certificate.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-lg hover:shadow-xl transition-all">
                    <x-lucide-plus class="h-5 w-5" />
                    {{ $s['createNew'] ?? '' }}
                </a>
            </div>

            <div class="text-center py-20 bg-white/80 rounded-2xl border border-slate-200 shadow-sm" x-show="count === 0" @if (count($items) > 0) x-cloak @endif>
                <x-lucide-scroll-text class="h-14 w-14 text-slate-300 mx-auto mb-4" />
                <h2 class="text-xl font-semibold text-gray-800">{{ $s['noItems'] ?? '' }}</h2>
                <p class="text-gray-600 mt-2 max-w-md mx-auto">{{ $s['noItemsDesc'] ?? '' }}</p>
                <a href="{{ route('work-certificate.create') }}" class="inline-flex items-center gap-2 mt-8 px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
                    <x-lucide-plus class="h-5 w-5" />
                    {{ $s['createFirst'] ?? '' }}
                </a>
            </div>

            <div class="grid gap-4 md:grid-cols-2" x-show="count > 0">
                @foreach ($items as $item)
                    <div x-show="isVisible({{ (int) $item['id'] }})" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col gap-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-semibold text-slate-900 line-clamp-2">{{ $item['title'] ?? '' }}</h3>
                                <p class="text-sm text-slate-600 mt-1">{{ $item['company_name'] ?? '' }}</p>
                                <p class="text-sm text-slate-500">{{ $item['employee_name'] ?? '' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-0.5">
                                <button type="button" @click="download('work-certificates/{{ (int) $item['id'] }}/pdf', @js($item['title'] ?: 'work-certificate'), { locale: @js($locale) })" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50" title="PDF" aria-label="Download PDF">
                                    <x-lucide-download class="h-5 w-5 shrink-0" />
                                </button>
                                <a href="{{ route('work-certificate.edit', $item['id']) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100" aria-label="{{ t('common.edit', [], 'Edit') }}" title="{{ t('common.edit', [], 'Edit') }}">
                                    <x-lucide-edit-2 class="h-5 w-5 shrink-0" />
                                </a>
                                <button type="button" @click="remove('work-certificates', {{ (int) $item['id'] }}, @js($item['title'] ?? ''))" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 hover:bg-red-50" aria-label="{{ t('common.delete', [], 'Delete') }}" title="{{ t('common.delete', [], 'Delete') }}">
                                    <x-lucide-trash-2 class="h-5 w-5 shrink-0" />
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <x-lucide-calendar class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                            {{ $s['lastModified'] ?? '' }}: {{ ui_date($item['updated_at'] ?? null, 'short', '—') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
