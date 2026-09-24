{{-- Port of pages/resumes.jsx --}}
@php
    $ownedIds = collect($owned)->pluck('id')->map(fn ($id) => (int) $id)->values();
    $pageState = [
        'ownedIds' => $ownedIds,
        'sharedCount' => count($shared),
        'limits' => $limits,
        'strings' => ['usageLabel' => t('resumes.usageLabel', [], '{{count}} of {{limit}} resume used')],
    ];
@endphp
<x-layouts.app :title="t('resumes.title').' | HResume'">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12" x-data="resumesPage(@js($pageState))">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 animate-slide-in">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <x-lucide-sparkles class="h-6 w-6 text-blue-600 animate-pulse-slow" />
                        <span class="text-blue-600 font-semibold">Your Resumes</span>
                    </div>
                    <h1 class="text-5xl md:text-6xl font-bold mb-2 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ t('resumes.title') }}</h1>
                    <p class="text-gray-600 text-lg" x-text="total + ' ' + (total === 1 ? 'resume' : 'resumes')">{{ count($owned) + count($shared) }} {{ count($owned) + count($shared) === 1 ? 'resume' : 'resumes' }}</p>
                    @if (($limits['owned_limit'] ?? null) !== null)
                        <p class="text-sm text-amber-700 mt-1" x-text="usageLabel()"></p>
                    @endif
                </div>
                <a href="{{ route('resume.create') }}" x-show="canCreate" @if (! ($limits['can_create'] ?? true)) x-cloak @endif
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" />
                    {{ t('resumes.createNew') }}
                </a>
                <button type="button" x-show="!canCreate" @if ($limits['can_create'] ?? true) x-cloak @endif @click="$store.upgrade.show('resume_limit')"
                    class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-crown class="h-5 w-5 mr-2" />
                    {{ t('resumes.upgradeToCreate') }}
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-12 text-center animate-slide-in" x-show="total === 0" @if (count($owned) + count($shared) > 0) x-cloak @endif>
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 mb-6">
                    <x-lucide-file-text class="h-10 w-10 text-blue-600" />
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">{{ t('resumes.noResumes') }}</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto text-lg">{{ t('resumes.noResumesDesc') }}</p>
                <a href="{{ route('resume.create') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" />
                    {{ t('resumes.createFirst') }}
                </a>
            </div>

            <div class="space-y-12" x-show="total > 0">
                @if (count($owned) > 0)
                    <div x-show="ownedCount > 0">
                        <div class="flex items-center gap-2 mb-6">
                            <x-lucide-file-text class="h-5 w-5 text-blue-600" />
                            <h2 class="text-2xl font-bold text-gray-900">My Resumes</h2>
                            <span class="ml-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold" x-text="ownedCount">{{ count($owned) }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($owned as $resume)
                                <x-resume-card :resume="$resume" />
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count($shared) > 0)
                    <div>
                        <div class="flex items-center gap-2 mb-6">
                            <x-lucide-users class="h-5 w-5 text-purple-600" />
                            <h2 class="text-2xl font-bold text-gray-900">Shared with Me</h2>
                            <span class="ml-2 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">{{ count($shared) }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($shared as $resume)
                                <x-resume-card :resume="$resume" :shared="true" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
