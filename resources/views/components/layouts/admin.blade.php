{{-- Port of Layouts/AdminLayout.jsx --}}
@php
    $user = auth()->user();
    $avatar = $user?->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Admin';
    $navItems = [
        ['path' => 'admin', 'icon' => 'bar-chart-3', 'label' => 'Dashboard'],
        ['path' => 'admin/ai-usage', 'icon' => 'sparkles', 'label' => 'AI usage'],
        ['path' => 'admin/emails', 'icon' => 'send', 'label' => 'Emails'],
        ['path' => 'admin/users', 'icon' => 'users', 'label' => 'Users'],
        ['path' => 'admin/templates', 'icon' => 'layout', 'label' => 'Templates'],
        ['path' => 'admin/cover-letter-templates', 'icon' => 'mail', 'label' => 'CL Templates'],
        ['path' => 'admin/blog', 'icon' => 'file-text', 'label' => 'Blog'],
        ['path' => 'admin/cvs', 'icon' => 'file-text', 'label' => 'Generated CVs'],
        ['path' => 'admin/cover-letters', 'icon' => 'mail', 'label' => 'Generated CLs'],
        ['path' => 'admin/work-certificates', 'icon' => 'scroll-text', 'label' => 'Work certs'],
        ['path' => 'admin/reviews', 'icon' => 'message-square', 'label' => 'Reviews'],
        ['path' => 'admin/fonts', 'icon' => 'type', 'label' => 'Fonts'],
    ];
    $isActive = fn (string $path) => $path === 'admin' ? request()->is('admin') : request()->is($path, $path.'/*');
@endphp
<x-layouts.base {{ $attributes }} robots="noindex, nofollow">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset
<div class="min-h-screen bg-gray-50 flex" x-data="{ mobile: false }">
    {{-- Mobile Overlay --}}
    <div x-show="mobile" x-cloak class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden" @click="mobile = false"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full"
        x-effect="$swap($el, mobile, 'translate-x-0', '-translate-x-full')"
    >
        <div class="h-full flex flex-col">
            <div class="h-20 flex items-center justify-between px-6 border-b border-gray-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-pink-600 flex items-center justify-center shadow-lg shadow-purple-200 group-hover:scale-105 transition-transform duration-300">
                        <x-lucide-shield class="h-6 w-6 text-white" />
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">Admin Panel</span>
                </a>
                <button type="button" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-500" @click="mobile = false">
                    <x-lucide-x class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1 custom-scrollbar">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4 px-2">Main Menu</div>
                @foreach ($navItems as $item)
                    @php $active = $isActive($item['path']); @endphp
                    <a href="{{ url($item['path']) }}" @class([
                        'flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group',
                        'bg-purple-50 text-purple-700 shadow-sm' => $active,
                        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
                    ])>
                        <div class="flex items-center">
                            <x-dynamic-component :component="'lucide-'.$item['icon']" @class([
                                'h-5 w-5 mr-3 transition-colors duration-200',
                                'text-purple-600' => $active,
                                'text-gray-400 group-hover:text-gray-600' => ! $active,
                            ]) />
                            {{ $item['label'] }}
                        </div>
                        @if ($active)
                            <x-lucide-chevron-right class="h-4 w-4" />
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                <a href="{{ route('admin.profile') }}" @class([
                    'flex items-center p-3 rounded-xl transition-all duration-200 mb-2',
                    'bg-white shadow-md ring-1 ring-purple-100' => $isActive('admin/profile'),
                    'hover:bg-white hover:shadow-sm' => ! $isActive('admin/profile'),
                ])>
                    <div class="relative">
                        <img class="h-10 w-10 rounded-full border-2 border-white shadow-sm" src="{{ $avatar }}" alt="{{ $user?->name }}" />
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="ml-3 overflow-hidden">
                        <p class="text-sm font-bold text-gray-900 truncate">{{ $user?->name }}</p>
                        <p class="text-xs text-purple-600 font-medium capitalize">Administrator</p>
                    </div>
                </a>

                <div class="flex gap-2">
                    <form method="POST" action="{{ route('logout') }}" class="flex-1 flex">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all duration-200">
                            <x-lucide-log-out class="h-4 w-4 mr-2" />
                            Sign out
                        </button>
                    </form>
                    <div class="p-1 bg-white border border-gray-200 rounded-lg">
                        <x-language-toggle />
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main Content Area --}}
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-300 lg:pl-72">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-30 px-4 md:px-8">
            <div class="h-full flex items-center justify-between">
                <div class="flex items-center">
                    <button type="button" @click="mobile = true" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 text-gray-600 mr-4">
                        <x-lucide-menu class="h-6 w-6" />
                    </button>
                    <div class="hidden md:flex items-center px-4 py-2 bg-gray-100 rounded-xl border border-gray-200 group focus-within:ring-2 focus-within:ring-purple-500/20 focus-within:bg-white transition-all duration-200">
                        <x-lucide-search class="h-4 w-4 text-gray-400 mr-3" />
                        <input type="text" placeholder="Search..." class="bg-transparent border-none focus:outline-none text-sm w-64 text-gray-700 placeholder:text-gray-400" />
                    </div>
                </div>

                <div class="flex items-center space-x-2 md:space-x-4">
                    <a href="{{ route('resumes.index') }}" class="hidden sm:flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-all duration-200">
                        <x-lucide-settings class="h-4 w-4 mr-2 text-gray-400" />
                        Back to App
                    </a>
                    <button type="button" class="p-2.5 rounded-xl hover:bg-gray-100 text-gray-600 relative group transition-all duration-200">
                        <x-lucide-bell class="h-5 w-5" />
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-pink-600 rounded-full ring-2 ring-white"></span>
                    </button>
                    <div class="h-8 w-[1px] bg-gray-200 mx-2 hidden sm:block"></div>
                    <a href="{{ route('admin.profile') }}" class="lg:hidden">
                        <img class="h-10 w-10 rounded-xl border border-gray-100 shadow-sm cursor-pointer" src="{{ $avatar }}" alt="{{ $user?->name }}" />
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
</x-layouts.base>
