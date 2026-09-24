@props(['resume', 'shared' => false])
{{-- ResumeCard from pages/resumes.jsx --}}
@php
    $template = $resume['template'] ?? null;
    $categoryColor = match ($template['category'] ?? null) {
        'Corporate' => 'bg-blue-100 text-blue-700',
        'Creative' => 'bg-purple-100 text-purple-700',
        default => 'bg-gray-100 text-gray-700',
    };
    $label = $template['name'] ?? $resume['name'];
    $ownerName = $resume['user']['name'] ?? $resume['user']['email'] ?? 'Unknown';
@endphp
<div x-show="isVisible({{ (int) $resume['id'] }})" class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2 border border-gray-100 group">
    <div class="relative h-56 bg-slate-50 flex items-start justify-center p-5 border-b border-gray-100">
        @if (! empty($template['preview_image_url']))
            <img src="{{ $template['preview_image_url'] }}" alt="{{ $label }}" loading="lazy" class="max-h-full w-auto object-contain drop-shadow"
                onerror="this.onerror=null; this.src='{{ placeholder_image(600, 800, '0f172a', 'ffffff', $label) }}';" />
        @else
            <div class="w-full h-full rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-lg font-semibold">{{ $label }}</div>
        @endif
        @if (! empty($template['category']))
            <span class="absolute top-4 left-4 px-3 py-1 rounded-full text-xs font-semibold {{ $categoryColor }}">{{ $template['category'] }}</span>
        @endif
        @if ($shared)
            <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">Shared</span>
        @else
            <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Free</span>
        @endif
    </div>

    <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 border-b border-gray-200">
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-1">{{ $resume['name'] }}</h3>
                @if ($template)
                    <p class="text-sm text-gray-500">{{ $template['name'] }}</p>
                @endif
                @if ($shared)
                    <p class="text-xs text-gray-500 mt-1">Shared by: {{ $ownerName }}</p>
                @endif
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
            <a href="{{ route('resume.edit', $resume['id']) }}" class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                <x-lucide-edit-2 class="h-4 w-4 mr-2" />
                {{ t('common.edit') }}
            </a>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-200 text-sm" title="{{ t('common.download') }}">
                    <x-lucide-download class="h-4 w-4 mr-1" />
                    {{ t('common.download') }}
                </button>
                @unless ($shared)
                    <button type="button" @click="remove({{ (int) $resume['id'] }}, @js($resume['name']))" :disabled="deleting" class="inline-flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 rounded-xl font-semibold hover:bg-red-100 transition-all duration-200 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <x-lucide-trash-2 class="h-4 w-4 mr-1" />
                        {{ t('common.delete') }}
                    </button>
                @endunless
            </div>
        </div>
    </div>
</div>
