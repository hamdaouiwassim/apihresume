{{--
    Generic host page for a React island (resume / cover letter / work certificate editors, shared resume, blog editor).
    Vars: $layout ('app' | 'guest' | 'admin'), $island (registry key in resources/js/islands.jsx), $title, $props (array), $robots
--}}
<x-dynamic-component :component="'layouts.'.$layout" :title="$title" :robots="$robots ?? 'noindex, nofollow'" :islands="true">
    <div data-island="{{ $island }}" data-props="{{ json_encode($props ?? [], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) }}">
        <div class="min-h-[60vh] flex flex-col items-center justify-center gap-3">
            <div class="h-10 w-10 rounded-full border-2 border-slate-200 border-t-blue-600 animate-spin" aria-hidden="true"></div>
            <p class="text-sm text-slate-600">{{ t('common.loading', [], 'Loading...') }}</p>
        </div>
    </div>
</x-dynamic-component>
