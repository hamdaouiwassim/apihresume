@props(['icon' => 'file-text'])
{{-- Section block shared by the Terms / Refund pages (Section component in the React version) --}}
<section>
    <div class="flex items-start gap-4">
        <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6 text-blue-600 mt-1 flex-shrink-0" />
        <div class="space-y-3">{{ $slot }}</div>
    </div>
</section>
