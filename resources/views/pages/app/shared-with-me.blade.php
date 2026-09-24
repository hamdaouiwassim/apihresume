{{-- Port of pages/SharedWithMe.jsx --}}
<x-layouts.app title="Shared with Me | HResume">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 animate-slide-in">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <x-lucide-users class="h-6 w-6 text-purple-600 animate-pulse-slow" />
                        <span class="text-purple-600 font-semibold">Shared Resumes</span>
                    </div>
                    <h1 class="text-5xl md:text-6xl font-bold mb-2 bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 bg-clip-text text-transparent">Shared with Me</h1>
                    <p class="text-gray-600 text-lg">{{ count($shared) }} {{ count($shared) === 1 ? 'resume' : 'resumes' }} shared with you</p>
                </div>
                <a href="{{ route('resumes.index') }}" class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-file-text class="h-5 w-5 mr-2" />
                    My Resumes
                </a>
            </div>

            @if (count($shared) === 0)
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center animate-slide-in">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 mb-6">
                        <x-lucide-users class="h-10 w-10 text-purple-600" />
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">No Shared Resumes Yet</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto text-lg">Resumes that are shared with you will appear here. When someone invites you to collaborate on their resume, you'll see it in this list.</p>
                    <a href="{{ route('resumes.index') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <x-lucide-file-text class="h-5 w-5 mr-2" />
                        View My Resumes
                    </a>
                </div>
            @else
                <div>
                    <div class="flex items-center gap-2 mb-6">
                        <x-lucide-users class="h-5 w-5 text-purple-600" />
                        <h2 class="text-2xl font-bold text-gray-900">Shared Resumes</h2>
                        <span class="ml-2 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">{{ count($shared) }}</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($shared as $resume)
                            @php
                                $template = $resume['template'] ?? null;
                                $label = $template['name'] ?? $resume['name'];
                                $categoryColor = match ($template['category'] ?? null) {
                                    'Corporate' => 'bg-blue-100 text-blue-700',
                                    'Creative' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2 border border-gray-100 group">
                                <div class="relative h-56 bg-slate-50 flex items-start justify-center p-5 border-b border-gray-100">
                                    @if (! empty($template['preview_image_url']))
                                        <img src="{{ $template['preview_image_url'] }}" alt="{{ $label }}" loading="lazy" class="max-h-full w-auto object-contain drop-shadow"
                                            onerror="this.onerror=null; this.src='{{ placeholder_image(600, 800, '0f172a', 'ffffff', $label) }}';" />
                                    @else
                                        <div class="w-full h-full rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-lg font-semibold">{{ $label }}</div>
                                    @endif
                                    @if (! empty($template['category']))
                                        <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-semibold {{ $categoryColor }}">{{ $template['category'] }}</span>
                                    @endif
                                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Shared</span>
                                </div>
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-6 border-b border-gray-200">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1">{{ $resume['name'] }}</h3>
                                            @if ($template)
                                                <p class="text-sm text-gray-500">{{ $template['name'] }}</p>
                                            @endif
                                            <p class="text-xs text-gray-500 mt-1">Shared by: {{ $resume['user']['name'] ?? $resume['user']['email'] ?? 'Unknown' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-3 mb-6">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <x-lucide-calendar class="h-4 w-4 mr-2 text-gray-400" />
                                            <span class="font-medium">{{ t('resumes.lastModified') }}:</span>
                                            <span class="ml-2">{{ ui_date($resume['updated_at'] ?? null) }}</span>
                                        </div>
                                        @if (! empty($resume['created_at']))
                                            <div class="flex items-center text-sm text-gray-600">
                                                <x-lucide-file-text class="h-4 w-4 mr-2 text-gray-400" />
                                                <span class="font-medium">Created:</span>
                                                <span class="ml-2">{{ ui_date($resume['created_at']) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('resume.edit', $resume['id']) }}" class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                                            <x-lucide-edit-2 class="h-4 w-4 mr-2" />
                                            {{ t('common.edit') }}
                                        </a>
                                        <button type="button" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-200 text-sm" title="{{ t('common.download') }}">
                                            <x-lucide-download class="h-4 w-4 mr-1" />
                                            {{ t('common.download') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
