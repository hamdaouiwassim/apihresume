@props(['post', 'hero' => false])
{{--
    Blog featured image with SEO/performance attributes.
    Optimized uploads (WebP variants): srcset + sizes + intrinsic width/height (no layout shift).
    Legacy or external URLs: plain src.
    hero = article header image (LCP): eager + fetchpriority high. Otherwise lazy.
--}}
@php
    $optimized = $post->optimizedFeaturedImage();
    $sizes = $hero
        ? '(min-width: 896px) 832px, calc(100vw - 32px)'
        : '(min-width: 1280px) 395px, (min-width: 1024px) calc((100vw - 128px) / 3), (min-width: 768px) calc((100vw - 96px) / 2), calc(100vw - 32px)';
@endphp
<img
    src="{{ $optimized['src'] ?? $post->featured_image }}"
    @if ($optimized)
        srcset="{{ $optimized['srcset'] }}"
        sizes="{{ $sizes }}"
        @if ($optimized['width'] && $optimized['height'])
            width="{{ $optimized['width'] }}"
            height="{{ $optimized['height'] }}"
        @endif
    @endif
    alt="{{ $post->title }}"
    decoding="async"
    @if ($hero)
        fetchpriority="high"
    @else
        loading="lazy"
    @endif
    {{ $attributes }}
/>
