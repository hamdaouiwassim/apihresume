{{-- Not-found state of pages/BlogPost.jsx (served with HTTP 404) --}}
@php $blog = (array) t('blog', [], []); @endphp
<x-layouts.guest :title="($blog['notFound'] ?? 'Blog Post Not Found').' | HResume'" robots="noindex, follow">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center py-20">
                <x-lucide-file-text class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $blog['notFound'] ?? 'Blog Post Not Found' }}</h1>
                <p class="text-gray-600 mb-6">{{ $blog['notFoundMessage'] ?? "The blog post you're looking for doesn't exist." }}</p>
                <a href="{{ route('blog.index') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200">
                    <x-lucide-arrow-left class="h-5 w-5 mr-2" />
                    {{ $blog['backToBlog'] ?? 'Back to Blog' }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.guest>
