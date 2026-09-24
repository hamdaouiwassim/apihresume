{{-- Port of pages/BlogPost.jsx (content already sanitized by BlogHtmlSanitizer) --}}
@php
    $blog = (array) t('blog', [], []);
    $jsonLd = [
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $description,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => $post->user?->name ?? 'HResume'],
        'publisher' => ['@id' => \App\Support\SchemaOrg::organizationId()],
        'isPartOf' => ['@id' => \App\Support\SchemaOrg::websiteId()],
        'mainEntityOfPage' => \App\Support\BlogSeo::postUrl($post),
    ];
    $optimized = $post->optimizedFeaturedImage();
    if ($ogImage) {
        $jsonLd['image'] = $optimized && $optimized['width']
            ? ['@type' => 'ImageObject', 'url' => $ogImage, 'width' => $optimized['width'], 'height' => $optimized['height']]
            : $ogImage;
    }
    $jsonLd = \App\Support\SchemaOrg::graph($jsonLd);
@endphp
<x-layouts.guest
    :title="$post->title.' | HResume Blog'"
    :description="$description"
    :canonical="'/blog/'.$post->slug"
    :image="$ogImage"
    :image-alt="$post->title"
    :image-width="$optimized['width'] ?? null"
    :image-height="$optimized['height'] ?? null"
    og-type="article"
    :json-ld="$jsonLd"
>
    @if ($optimized)
        <x-slot:head>
            {{-- Preload the hero image (Largest Contentful Paint) with the same responsive candidates --}}
            <link rel="preload" as="image" href="{{ $optimized['src'] }}" imagesrcset="{{ $optimized['srcset'] }}" imagesizes="(min-width: 896px) 832px, calc(100vw - 32px)" fetchpriority="high">
        </x-slot:head>
    @endif
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('blog.index') }}" class="inline-flex items-center text-gray-600 hover:text-blue-600 mb-8 transition-colors">
                <x-lucide-arrow-left class="h-5 w-5 mr-2" />
                {{ $blog['backToBlog'] ?? 'Back to Blog' }}
            </a>

            <article class="bg-white rounded-2xl shadow-xl overflow-hidden">
                @if ($post->featured_image)
                    <div class="aspect-video w-full overflow-hidden bg-gray-200">
                        <x-blog-image :post="$post" :hero="true" class="w-full h-full object-cover" />
                    </div>
                @endif

                <div class="p-8 md:p-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">{{ $post->title }}</h1>

                    <div class="flex flex-wrap items-center gap-6 mb-8 pb-8 border-b border-gray-200">
                        <div class="flex items-center gap-2 text-gray-600">
                            <x-lucide-user class="h-5 w-5" />
                            <span>{{ $post->user?->name ?? 'Admin' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <x-lucide-calendar class="h-5 w-5" />
                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('F j, Y') }}</time>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <x-lucide-eye class="h-5 w-5" />
                            <span>{{ $post->views ?? 0 }} {{ $blog['views'] ?? 'views' }}</span>
                        </div>
                    </div>

                    @if ($post->excerpt)
                        <p class="text-xl text-gray-700 mb-8 font-medium">{{ $post->excerpt }}</p>
                    @endif

                    <div class="blog-post-content max-w-none">{!! $post->content !!}</div>
                </div>
            </article>
        </div>
    </div>
</x-layouts.guest>
