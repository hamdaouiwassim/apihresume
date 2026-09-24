@props(['name', 'type' => 'text', 'icon', 'placeholder', 'label', 'autocomplete' => null, 'showError' => false])
{{-- Icon input used by the login / register forms --}}
<div>
    <label for="{{ $name }}" class="sr-only">{{ $label }}</label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-dynamic-component :component="'lucide-'.$icon" class="h-5 w-5 text-slate-400" />
        </div>
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            required
            x-model="form.{{ $name }}"
            class="appearance-none relative block w-full px-3 py-3 pl-10 border border-slate-300 placeholder-slate-500 text-slate-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
            placeholder="{{ $placeholder }}"
        />
    </div>
    @if ($showError)
        <p x-show="errors.{{ $name }}" x-cloak class="mt-2 text-sm text-red-600" x-text="errors.{{ $name }} && errors.{{ $name }}[0]"></p>
    @endif
</div>
