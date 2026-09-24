{{-- Port of pages/Blog.jsx --}}
@php $blog = (array) t('blog', [], []); @endphp
<x-layouts.guest
    :title="($blog['title'] ?? 'Blog').' | HResume'"
    :description="$blog['subtitle'] ?? 'Stay updated with the latest tips, insights, and news about resume building and career development.'"
    :canonical="'/blog'.($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : '')"
    :robots="$search !== '' ? 'noindex, follow' : 'index, follow'"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-file-text class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $blog['title'] ?? 'Blog' }}</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $blog['subtitle'] ?? 'Stay updated with the latest tips, insights, and news about resume building and career development.' }}</p>
            </div>

            <div class="mb-8">
                <form method="GET" action="{{ route('blog.index') }}" class="max-w-2xl mx-auto">
                    <div class="relative">
                        <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ $blog['searchPlaceholder'] ?? 'Search blog posts...' }}"
                            class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </form>
            </div>

            @if ($posts->isEmpty())
                <div class="text-center py-20">
                    <x-lucide-file-text class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-600 text-lg">{{ $blog['noPosts'] ?? 'No blog posts found.' }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                    @foreach ($posts as $post)
                        <a href="{{ route('blog.show', $post->slug, false) }}" class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group">
                            @if ($post->featured_image)
                                <div class="aspect-video w-full overflow-hidden bg-gray-200">
                                    <x-blog-image :post="$post" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" />
                                </div>
                            @endif
                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors line-clamp-2">{{ $post->title }}</h2>
                                @if ($post->excerpt)
                                    <p class="text-gray-600 mb-4 line-clamp-3">{{ $post->excerpt }}</p>
                                @endif
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1">
                                            <x-lucide-user class="h-4 w-4" />
                                            <span>{{ $post->user?->name ?? 'Admin' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <x-lucide-calendar class="h-4 w-4" />
                                            <span>{{ $post->published_at?->format('F j, Y') }}</span>
                                        </div>
                                    </div>
                                    <x-lucide-arrow-right class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($posts->lastPage() > 1)
                    @php $btn = 'px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50'; @endphp
                    <nav class="flex justify-center items-center gap-2" aria-label="Pagination">
                        @if ($posts->onFirstPage())
                            <span class="{{ $btn }} opacity-50 cursor-not-allowed">{{ $blog['previous'] ?? 'Previous' }}</span>
                        @else
                            <a href="{{ $posts->previousPageUrl() }}" rel="prev" class="{{ $btn }}">{{ $blog['previous'] ?? 'Previous' }}</a>
                        @endif
                        <span class="px-4 py-2 text-gray-700">{{ $blog['page'] ?? 'Page' }} {{ $posts->currentPage() }} {{ $blog['of'] ?? 'of' }} {{ $posts->lastPage() }}</span>
                        @if ($posts->hasMorePages())
                            <a href="{{ $posts->nextPageUrl() }}" rel="next" class="{{ $btn }}">{{ $blog['next'] ?? 'Next' }}</a>
                        @else
                            <span class="{{ $btn }} opacity-50 cursor-not-allowed">{{ $blog['next'] ?? 'Next' }}</span>
                        @endif
                    </nav>
                @endif
            @endif
        </div>
    </div>
</x-layouts.guest>
