@props(['title', 'icon', 'iconClass', 'href' => null, 'hrefClass' => '', 'empty', 'count' => 0])
{{-- RecentSection from admin/Dashboard.jsx --}}
<section class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-100/80">
    <header class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gradient-to-r from-gray-50/80 to-white px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconClass }}">
                <x-dynamic-component :component="'lucide-'.$icon" class="h-4 w-4" />
            </div>
            <h2 class="text-base font-bold text-gray-900">{{ $title }}</h2>
        </div>
        @if ($href)
            <a href="{{ url($href) }}" class="text-xs font-semibold hover:underline {{ $hrefClass }}">View all</a>
        @endif
    </header>
    <div class="flex-1 p-4">
        @if ($count > 0)
            <ul class="space-y-2">{{ $slot }}</ul>
        @else
            <p class="py-8 text-center text-sm text-gray-400">{{ $empty }}</p>
        @endif
    </div>
</section>
