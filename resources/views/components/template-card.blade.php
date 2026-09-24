@props(['template'])
{{-- Template card from the home page (welcome.jsx templates grid) --}}
@php
    $name = $template->name;
    $category = $template->category ?: 'Professional';
    $image = $template->preview_image_url ?? null;
@endphp
<a href="{{ route('templates.public.preview', $template->id) }}" class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 overflow-hidden group block">
    <div class="relative">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $name }} Template" class="w-full h-48 object-cover" loading="lazy"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
        @endif
        <div class="{{ $image ? 'hidden' : '' }} bg-gradient-to-br from-blue-50 to-white h-48 p-4 border-b">
            <div class="bg-white rounded-lg p-3 shadow-sm mb-3 border-l-4 border-blue-500">
                <div class="h-3 bg-gray-800 rounded w-3/4 mb-2"></div>
                <div class="h-2 bg-gray-400 rounded w-1/2"></div>
            </div>
            <div class="space-y-2">
                <div class="flex space-x-2">
                    <div class="h-2 bg-blue-200 rounded w-16"></div>
                    <div class="h-2 bg-gray-300 rounded flex-1"></div>
                </div>
                <div class="flex space-x-2">
                    <div class="h-2 bg-blue-200 rounded w-20"></div>
                    <div class="h-2 bg-gray-300 rounded flex-1"></div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-4 left-4 text-white drop-shadow-lg">
            <h3 class="text-xl font-bold">{{ $name }}</h3>
            <p class="text-sm opacity-90">{{ $category }}</p>
        </div>
        <div class="absolute inset-0  bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300 flex items-center justify-center">
            <span class="bg-white text-blue-600 px-6 py-2 rounded-full font-semibold opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-4 group-hover:translate-y-0">Preview Template</span>
        </div>
    </div>
    <div class="p-6">
        <h4 class="text-lg font-bold mb-2 text-gray-900">{{ $name }} Template</h4>
        <p class="text-gray-600 text-sm leading-relaxed">{{ $template->description ?: 'Professional '.mb_strtolower($name).' template for all industries.' }}</p>
        <div class="mt-4 flex flex-wrap gap-1">
            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $category }}</span>
            @if ($template->category === 'Corporate')
                <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Business</span>
            @endif
        </div>
    </div>
</a>
