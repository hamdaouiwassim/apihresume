{{-- QuickActionCard from admin/Dashboard.jsx --}}
<a href="{{ url($to) }}" class="group flex items-start gap-4 rounded-xl border p-4 transition-all duration-200 hover:-translate-y-0.5 {{ $classes }}">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $variant === 'primary' ? 'bg-white/20' : 'bg-purple-100 text-purple-700' }}">
        <x-dynamic-component :component="'lucide-'.$icon" class="h-5 w-5" />
    </div>
    <div class="min-w-0 flex-1">
        <p class="font-semibold {{ $variant === 'primary' ? 'text-white' : 'text-gray-900' }}">{{ $title }}</p>
        <p class="mt-0.5 text-xs leading-relaxed {{ $variant === 'primary' ? 'text-white/85' : 'text-gray-500' }}">{{ $desc }}</p>
    </div>
    <x-lucide-chevron-right class="mt-1 h-5 w-5 shrink-0 opacity-50 transition-transform group-hover:translate-x-0.5 {{ $variant === 'primary' ? 'text-white' : 'text-gray-400' }}" />
</a>
