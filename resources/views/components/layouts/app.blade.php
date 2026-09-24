{{-- Port of Layouts/AuthLayout.jsx (signed-in user area) --}}
@php
    $user = auth()->user();
    $avatar = user_avatar($user);
    $isResumes = request()->is('resumes') || (request()->is('resume/*') && ! request()->is('resume/edit/*'));
    $isCoverLetters = request()->is('cover-letters') || request()->is('cover-letter/*');
    $isWorkCerts = request()->is('work-certificates') || request()->is('work-certificate/*');
    $isShared = request()->is('shared-with-me');
    $isTemplates = request()->is('templates');
    $githubStrings = [
        'success' => t('profile.githubImport.connectSuccess', [], 'GitHub connected. You can import repositories you have access to.'),
        'error' => t('profile.githubImport.connectError', [], 'GitHub connection failed.'),
    ];
@endphp
<x-layouts.base {{ $attributes }} robots="noindex, nofollow">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset
<div
    class="min-h-screen bg-gray-50"
    x-data="{
        dropdown: false,
        mobile: false,
        init() {
            const params = new URLSearchParams(window.location.search);
            const status = params.get('github_import');
            if (!status) return;
            const raw = params.get('message');
            const message = raw ? decodeURIComponent(raw.replace(/\+/g, ' ')) : '';
            const strings = @js($githubStrings);
            if (status === 'success') window.toast.success(strings.success);
            else if (status === 'error') window.toast.error(message || strings.error);
            params.delete('github_import');
            params.delete('message');
            const qs = params.toString();
            history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
        }
    }"
>
    <nav class="bg-white/95 backdrop-blur-md shadow-lg fixed w-full z-50 border-b border-gray-200/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ localized_url('/') }}" class="flex items-center space-x-2 group">
                            <img src="/logo.webp" width="40" height="40" alt="HResume Logo" class="h-10 w-auto group-hover:scale-110 transition-transform duration-300" />
                            <span class="text-xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-blue-800 bg-clip-text text-transparent">HResume</span>
                        </a>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:items-center md:space-x-2">
                        <a href="{{ route('resumes.index') }}" @class([
                            'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg shadow-blue-500/50' => $isResumes,
                            'text-gray-600 hover:text-blue-600 hover:bg-gray-100' => ! $isResumes,
                        ])>
                            <x-lucide-file-text class="h-4 w-4 mr-1.5" />
                            {{ t('nav.myResumes', [], 'My Resumes') }}
                        </a>
                        <a href="{{ route('cover-letters.index') }}" @class([
                            'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg shadow-blue-500/50' => $isCoverLetters,
                            'text-gray-600 hover:text-blue-600 hover:bg-gray-100' => ! $isCoverLetters,
                        ])>
                            <x-lucide-file-text class="h-4 w-4 mr-1.5" />
                            {{ t('nav.myCoverLetters', [], 'Cover Letters') }}
                        </a>
                        <a href="{{ route('work-certificates.index') }}" @class([
                            'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-lg shadow-indigo-500/50' => $isWorkCerts,
                            'text-gray-600 hover:text-indigo-600 hover:bg-gray-100' => ! $isWorkCerts,
                        ])>
                            <x-lucide-scroll-text class="h-4 w-4 mr-1.5" />
                            {{ t('nav.workCertification', [], 'Work certification') }}
                        </a>
                        <a href="{{ route('shared-with-me') }}" @class([
                            'inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                            'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg shadow-purple-500/50' => $isShared,
                            'text-gray-600 hover:text-purple-600 hover:bg-gray-100' => ! $isShared,
                        ])>
                            <x-lucide-users class="h-4 w-4 mr-1.5" />
                            Shared with Me
                        </a>
                    </div>
                </div>

                {{-- User dropdown menu --}}
                <div class="hidden md:ml-4 md:flex md:items-center md:space-x-3">
                    <x-language-toggle />
                    <x-collaboration-notifications />
                    <div class="relative">
                        <button
                            type="button"
                            @click="dropdown = !dropdown"
                            class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none px-2 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 transition-all duration-200 group"
                        >
                            <img class="h-9 w-9 rounded-full border-2 border-blue-500 group-hover:border-purple-500 transition-colors duration-200 ring-2 ring-offset-2 ring-blue-500/20" src="{{ $avatar }}" alt="{{ $user->name }}" />
                            <span class="hidden lg:block font-medium">{{ $user->name }}</span>
                            <x-lucide-chevron-down class="h-4 w-4 transition-transform duration-200" ::class="dropdown ? 'rotate-180' : ''" />
                        </button>

                        <template x-if="dropdown">
                            <div>
                                <div class="fixed inset-0 z-10" @click="dropdown = false"></div>
                                <div class="origin-top-right absolute right-0 mt-2 w-56 rounded-lg shadow-xl py-1.5 bg-white/95 backdrop-blur-md ring-1 ring-black ring-opacity-5 focus:outline-none z-20">
                                    <div class="px-3 py-2.5 border-b border-gray-200">
                                        <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500 truncate">{{ $user->email }}</p>
                                    </div>
                                    <a href="{{ route('profile') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 hover:text-blue-600 transition-all duration-200">
                                        <x-lucide-user class="h-4 w-4 mr-2.5" />
                                        {{ t('nav.profile', [], 'Profile') }}
                                    </a>
                                    <a href="{{ route('review') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 hover:text-pink-600 transition-all duration-200">
                                        <x-lucide-heart class="h-4 w-4 mr-2.5" />
                                        {{ t('nav.review', [], 'Leave a Review') }}
                                    </a>
                                    @if ($user->is_admin)
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-sm text-purple-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 hover:text-purple-600 transition-all duration-200 font-semibold">
                                            <x-lucide-shield class="h-4 w-4 mr-2.5" />
                                            {{ t('nav.adminPanel', [], 'Admin Panel') }}
                                        </a>
                                    @endif
                                    <div class="border-t border-gray-200 my-1"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-all duration-200">
                                            <x-lucide-log-out class="h-4 w-4 mr-2.5" />
                                            {{ t('nav.logout', [], 'Sign out') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Mobile menu button --}}
                <div class="flex items-center md:hidden space-x-2">
                    <x-language-toggle />
                    <div class="md:hidden">
                        <x-collaboration-notifications />
                    </div>
                    <button
                        type="button"
                        @click="mobile = !mobile"
                        class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-all duration-200"
                    >
                        <x-lucide-x class="block h-6 w-6" x-show="mobile" x-cloak />
                        <x-lucide-menu class="block h-6 w-6" x-show="!mobile" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div class="md:hidden transition-all duration-300 ease-in-out overflow-hidden" :class="mobile ? 'max-h-screen opacity-100' : 'max-h-0 opacity-0'" x-cloak>
            <div class="px-3 pt-2 pb-3 space-y-1 bg-white/95 backdrop-blur-md border-t border-gray-200">
                <a href="{{ route('resumes.index') }}" @class([
                    'flex items-center px-3 py-2.5 rounded-lg text-base font-semibold transition-all duration-200',
                    'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' => $isResumes,
                    'text-gray-600 hover:bg-gray-100 hover:text-blue-600' => ! $isResumes,
                ])>
                    <x-lucide-file-text class="h-4 w-4 mr-2" />
                    {{ t('nav.myResumes', [], 'My Resumes') }}
                </a>
                <a href="{{ route('cover-letters.index') }}" @class([
                    'flex items-center px-3 py-2.5 rounded-lg text-base font-semibold transition-all duration-200',
                    'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' => $isCoverLetters,
                    'text-gray-600 hover:bg-gray-100 hover:text-blue-600' => ! $isCoverLetters,
                ])>
                    <x-lucide-file-text class="h-4 w-4 mr-2" />
                    {{ t('nav.myCoverLetters', [], 'Cover Letters') }}
                </a>
                <a href="{{ route('work-certificates.index') }}" @class([
                    'flex items-center px-3 py-2.5 rounded-lg text-base font-semibold transition-all duration-200',
                    'bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-lg' => $isWorkCerts,
                    'text-gray-600 hover:bg-gray-100 hover:text-indigo-600' => ! $isWorkCerts,
                ])>
                    <x-lucide-scroll-text class="h-4 w-4 mr-2" />
                    {{ t('nav.workCertification', [], 'Work certification') }}
                </a>
                <a href="{{ route('shared-with-me') }}" @class([
                    'block px-3 py-2.5 rounded-lg text-base font-semibold transition-all duration-200',
                    'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg' => $isShared,
                    'text-gray-600 hover:bg-gray-100 hover:text-purple-600' => ! $isShared,
                ])>
                    <x-lucide-users class="h-4 w-4 inline mr-2" />
                    Shared with Me
                </a>
                <a href="{{ route('templates.index') }}" @class([
                    'block px-3 py-2.5 rounded-lg text-base font-semibold transition-all duration-200',
                    'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg' => $isTemplates,
                    'text-gray-600 hover:bg-gray-100 hover:text-blue-600' => ! $isTemplates,
                ])>
                    {{ t('nav.templates', [], 'Templates') }}
                </a>

                <div class="pt-3 border-t border-gray-200 mt-3">
                    <div class="flex items-center px-3 py-2.5 mb-2 rounded-lg bg-gradient-to-r from-blue-50 to-purple-50">
                        <img class="h-10 w-10 rounded-full border-2 border-blue-500" src="{{ $avatar }}" alt="{{ $user->name }}" />
                        <div class="ml-2.5">
                            <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('profile') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 hover:text-blue-600 transition-all duration-200">
                            <x-lucide-user class="h-4 w-4 mr-2.5" />
                            {{ t('nav.profile', [], 'Profile') }}
                        </a>
                        <a href="{{ route('review') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 hover:text-pink-600 transition-all duration-200">
                            <x-lucide-heart class="h-4 w-4 mr-2.5" />
                            {{ t('nav.review', [], 'Leave a Review') }}
                        </a>
                        <a href="{{ route('profile') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 hover:text-blue-600 transition-all duration-200">
                            <x-lucide-settings class="h-4 w-4 mr-2.5" />
                            {{ t('nav.settings', [], 'Settings') }}
                        </a>
                        @if ($user->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 rounded-lg text-sm font-semibold text-purple-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 hover:text-purple-600 transition-all duration-200">
                                <x-lucide-shield class="h-4 w-4 mr-2.5" />
                                {{ t('nav.adminPanel', [], 'Admin Panel') }}
                            </a>
                        @endif
                        <div class="border-t border-gray-200 my-1.5"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-all duration-200">
                                <x-lucide-log-out class="h-4 w-4 mr-2.5" />
                                {{ t('nav.logout', [], 'Sign out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-16">
        {{ $slot }}
    </main>
</div>
</x-layouts.base>
