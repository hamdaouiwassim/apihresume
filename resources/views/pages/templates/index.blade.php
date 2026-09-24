{{-- Port of pages/templates.jsx (signed-in template browser, filtered client-side like before) --}}
@php
    $tt = (array) t('templates', [], []);
    $categories = ['All', 'Corporate', 'Creative', 'Simple'];
    $categoryColor = fn ($c) => match ($c) {
        'Corporate' => 'bg-blue-100 text-blue-700',
        'Creative' => 'bg-purple-100 text-purple-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp
<x-layouts.app :title="($tt['title'] ?? 'Templates').' | HResume'">
    <div
        class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12"
        x-data="{
            category: 'All',
            query: '',
            items: @js($templates->map(fn ($tpl) => ['id' => $tpl->id, 'name' => $tpl->name, 'description' => $tpl->description, 'category' => $tpl->category])->values()),
            matches(item) {
                if (this.category !== 'All' && item.category !== this.category) return false;
                const q = this.query.trim().toLowerCase();
                if (!q) return true;
                return item.name.toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q);
            },
            get visibleCount() { return this.items.filter((i) => this.matches(i)).length; },
        }"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-slide-in">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <x-lucide-sparkles class="h-6 w-6 text-blue-600 animate-pulse-slow" />
                    <span class="text-blue-600 font-semibold">Professional Templates</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $tt['title'] ?? 'Templates' }}</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ $tt['subtitle'] ?? '' }}</p>
            </div>

            <div class="max-w-md mx-auto mb-8">
                <div class="relative">
                    <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input type="text" placeholder="Search templates..." x-model="query" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 shadow-sm" />
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 mb-12">
                @foreach ($categories as $category)
                    <button
                        type="button"
                        @click="category = @js($category)"
                        class="px-6 py-2.5 rounded-full text-sm font-semibold transition-all duration-200 transform hover:scale-105 {{ $category === 'All' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg shadow-blue-500/50' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm' }}"
                        x-effect="$swap($el, category === @js($category), 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg shadow-blue-500/50', 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm')"
                    >{{ $category === 'All' ? ($tt['allCategories'] ?? 'All') : ($tt[strtolower($category)] ?? $category) }}</button>
                @endforeach
            </div>

            <div class="text-center py-20" x-show="visibleCount === 0" @if ($templates->isNotEmpty()) x-cloak @endif>
                <p class="text-xl text-gray-600 mb-4">No templates found</p>
                <p class="text-gray-500" x-text="query ? 'Try a different search term' : 'No templates in this category'">No templates in this category</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" x-show="visibleCount > 0">
                @foreach ($templates as $template)
                    <div
                        x-show="matches(items[{{ $loop->index }}])"
                        class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2 border border-gray-100 group"
                    >
                        <div class="relative h-64 bg-slate-50 flex items-start justify-center p-5">
                            @if ($template->preview_image_url)
                                <img src="{{ $template->preview_image_url }}" alt="{{ $template->name }}" loading="lazy"
                                    class="max-h-full w-auto object-contain transition-transform duration-300 group-hover:scale-105 drop-shadow"
                                    onerror="this.onerror=null; this.src='{{ placeholder_image(800, 1000, '667eea', 'ffffff', $template->name) }}';" />
                            @else
                                <div class="w-full h-full rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold">{{ $template->name }}</div>
                            @endif
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center">
                                <a href="{{ route('templates.preview', $template->id) }}" class="px-6 py-3 bg-white text-blue-600 rounded-xl flex items-center font-semibold transform translate-y-4 opacity-0 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 shadow-lg hover:shadow-xl">
                                    <x-lucide-eye class="h-5 w-5 mr-2" />
                                    {{ $tt['preview'] ?? 'Preview' }}
                                </a>
                            </div>
                            <div class="absolute top-4 right-4 flex gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $categoryColor($template->category) }}">{{ $template->category }}</span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Free</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $template->name }}</h3>
                            <p class="text-gray-600 mb-6 line-clamp-2">{{ $template->description ?: 'A professional template for your resume' }}</p>
                            <a href="{{ route('resume.create', ['template' => $template->id]) }}" class="inline-flex items-center w-full justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                                {{ $tt['useTemplate'] ?? 'Use Template' }}
                                <x-lucide-arrow-right class="h-5 w-5 ml-2" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
