@props(['rows' => 4])
{{-- Port of components/AiLoadingSkeleton.jsx --}}
<div {{ $attributes->class('rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3 animate-pulse') }} role="status" aria-live="polite">
    <div class="h-3 w-1/3 rounded bg-slate-200"></div>
    @for ($i = 0; $i < $rows; $i++)
        <div class="h-3 rounded bg-slate-200" style="width: {{ 85 - $i * 12 }}%"></div>
    @endfor
    <span class="sr-only">Loading AI result</span>
</div>
