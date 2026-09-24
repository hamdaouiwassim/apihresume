{{-- Port of pages/CoverLetters.jsx --}}
@php
    $s = (array) t('coverLetter', [], []);
    $state = [
        'ids' => collect($letters)->pluck('id')->map(fn ($id) => (int) $id)->values(),
        'strings' => [
            'confirmTitle' => $s['messages']['deleteConfirmTitle'] ?? 'Delete Cover Letter',
            'confirmMessage' => $s['messages']['deleteConfirmMessage'] ?? 'Are you sure you want to delete this cover letter? This action cannot be undone.',
            'confirmText' => t('common.confirmDelete', [], 'Yes, Delete'),
            'cancelText' => t('common.cancel', [], 'Cancel'),
            'deleteSuccess' => $s['messages']['deleteSuccess'] ?? 'Deleted successfully',
            'deleteError' => $s['messages']['deleteError'] ?? 'Failed to delete',
            'pdfError' => 'Failed to download PDF',
        ],
    ];
    $one = $s['letterCountOne'] ?? 'letter';
    $other = $s['letterCountOther'] ?? 'letters';
@endphp
<x-layouts.app :title="($s['title'] ?? 'Cover Letters').' | HResume'">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12" x-data="documentList(@js($state))">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 animate-slide-in">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <x-lucide-sparkles class="h-6 w-6 text-blue-600 animate-pulse-slow" />
                        <span class="text-blue-600 font-semibold">{{ $s['title'] ?? '' }}</span>
                    </div>
                    <h1 class="text-5xl md:text-6xl font-bold mb-2 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $s['title'] ?? '' }}</h1>
                    <p class="text-gray-600 text-lg" x-text="count + ' ' + (count === 1 ? @js($one) : @js($other))">{{ count($letters) }} {{ count($letters) === 1 ? $one : $other }}</p>
                </div>
                <a href="{{ route('cover-letter.create') }}" class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" />
                    {{ $s['createNew'] ?? '' }}
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-12 text-center animate-slide-in" x-show="count === 0" @if (count($letters) > 0) x-cloak @endif>
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 mb-6">
                    <x-lucide-file-text class="h-10 w-10 text-blue-600" />
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">{{ $s['noLetters'] ?? '' }}</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto text-lg">{{ $s['noLettersDesc'] ?? '' }}</p>
                <a href="{{ route('cover-letter.create') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" />
                    {{ $s['createFirst'] ?? '' }}
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="count > 0">
                @foreach ($letters as $letter)
                    <div x-show="isVisible({{ (int) $letter['id'] }})" class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2 border border-gray-100 group">
                        <div class="relative h-40 bg-slate-50 flex items-center justify-center border-b border-gray-100">
                            <x-lucide-file-text class="h-20 w-20 text-blue-200 group-hover:text-blue-300 transition-colors" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent"></div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1">{{ $letter['title'] }}</h3>
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center text-sm text-gray-600">
                                    <x-lucide-calendar class="h-4 w-4 mr-2 text-gray-400" />
                                    <span class="font-medium">{{ $s['lastModified'] ?? '' }}:</span>
                                    <span class="ml-2">{{ ui_date($letter['updated_at'] ?? null) }}</span>
                                </div>
                                <p class="text-xs text-gray-500 truncate">To: {{ $letter['recipient_name'] ?? null ?: 'N/A' }}</p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('cover-letter.edit', $letter['id']) }}" class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                                    <x-lucide-edit-2 class="h-4 w-4 mr-2" />
                                    {{ t('common.edit') }}
                                </a>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="download('cover-letters/{{ (int) $letter['id'] }}/pdf', @js($letter['title']))" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-200 text-sm">
                                        <x-lucide-download class="h-4 w-4 mr-1" />
                                        {{ t('common.download') }}
                                    </button>
                                    <button type="button" @click="remove('cover-letters', {{ (int) $letter['id'] }}, @js($letter['title']))" class="inline-flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 rounded-xl font-semibold hover:bg-red-100 transition-all duration-200 text-sm">
                                        <x-lucide-trash-2 class="h-4 w-4 mr-1" />
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
