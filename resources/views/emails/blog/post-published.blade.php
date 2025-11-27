<x-mail::message>
# New Blog Post Published! 🎉

We're excited to share a new blog post with you!

## {{ $post->title }}

@if($post->excerpt)
{{ $post->excerpt }}
@endif

<x-mail::button :url="$postUrl">
Read Full Article
</x-mail::button>

@if($post->featured_image)
<img src="{{ $post->featured_image }}" alt="{{ $post->title }}" style="max-width: 100%; height: auto; margin: 20px 0; border-radius: 8px;">
@endif

Stay tuned for more updates and insights!

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
