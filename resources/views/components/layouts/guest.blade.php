@props(['navVariant' => 'default'])
@php
    $user = auth()->user();
    $isHeroVariant = $navVariant === 'hero';
    // Prints class="base + initial variant" plus an Alpine x-effect that swaps hero/default sets on scroll.
    $sw = function (string $base, string $hero, string $default) use ($isHeroVariant): string {
        $initial = e($base.' '.($isHeroVariant ? $hero : $default));

        return "class=\"{$initial}\" x-effect=\"swap(\$el, isHero, '{$hero}', '{$default}')\"";
    };
    $resumeCount = \Illuminate\Support\Facades\Cache::remember('stats.total_resumes', 300, fn () => \App\Models\Resume::count());
@endphp
<x-layouts.base {{ $attributes }}>
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset
<div class="min-h-screen w-full">
    <nav x-data="guestNav(@js($navVariant))" {!! $sw('fixed z-50 w-full transition-[background-color,border-color,box-shadow] duration-300', 'guest-nav-hero border-b border-white/10 shadow-lg shadow-black/25', 'border-b border-gray-200 bg-white shadow-lg') !!}>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex-shrink-0 flex items-center space-x-2 group">
                        <img src="/logo.webp" width="40" height="40" alt="HResume Logo" class="h-10 w-auto group-hover:scale-110 transition-transform duration-300" />
                        <span {!! $sw('text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r', 'from-blue-200 via-purple-200 to-violet-200', 'from-blue-600 via-purple-600 to-blue-800') !!}>HResume</span>
                    </a>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden md:flex md:items-center md:space-x-4">
                    <x-language-toggle :tone="$isHeroVariant ? 'dark' : 'light'" tone-expr="isHero" />
                    @guest
                        <a href="{{ route('login') }}" {!! $sw('inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200', 'text-slate-200 hover:bg-white/10 hover:text-white', 'text-gray-600 hover:bg-gray-100 hover:text-blue-600') !!}>
                            <x-lucide-log-in class="h-4 w-4 shrink-0" aria-hidden="true" />
                            Login
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white hover:from-blue-600 hover:to-purple-700 px-6 py-2 rounded-lg text-sm font-semibold shadow-lg shadow-blue-500/50 hover:shadow-xl hover:shadow-blue-500/50 transition-all duration-200 transform hover:-translate-y-0.5"
                        >
                            <x-lucide-user-plus class="h-4 w-4 shrink-0" aria-hidden="true" />
                            Get Started
                        </a>
                    @else
                        <a
                            href="{{ route('resumes.index') }}"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white hover:from-blue-600 hover:to-purple-700 px-6 py-2 rounded-lg text-sm font-semibold shadow-lg shadow-blue-500/50 hover:shadow-xl hover:shadow-blue-500/50 transition-all duration-200 transform hover:-translate-y-0.5"
                        >
                            <x-lucide-layout-dashboard class="h-4 w-4 shrink-0" aria-hidden="true" />
                            Dashboard
                        </a>
                    @endguest
                </div>

                {{-- Mobile menu button --}}
                <div class="flex items-center space-x-2 md:hidden">
                    <x-language-toggle :tone="$isHeroVariant ? 'dark' : 'light'" tone-expr="isHero" />
                    <button type="button" @click="open = !open" {!! $sw('inline-flex items-center justify-center rounded-lg p-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-inset', 'text-slate-200 hover:bg-white/10 hover:text-white focus:ring-violet-400/40', 'text-gray-600 hover:bg-gray-100 hover:text-blue-600 focus:ring-blue-500') !!}>
                        <span class="sr-only">Open main menu</span>
                        <x-lucide-x class="block h-6 w-6" x-show="open" x-cloak />
                        <x-lucide-menu class="block h-6 w-6" x-show="!open" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile menu with smooth animation --}}
        <div class="md:hidden transition-all duration-300 ease-in-out overflow-hidden max-h-0 opacity-0" x-effect="swap($el, open, 'max-h-screen opacity-100', 'max-h-0 opacity-0')">
            <div {!! $sw('space-y-2 border-t px-4 pt-2 pb-4', 'border-white/10 bg-slate-950/95', 'border-gray-200 bg-white') !!}>
                @guest
                    <a href="{{ route('login') }}" {!! $sw('flex items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold transition-all duration-200', 'text-slate-200 hover:bg-white/10 hover:text-white', 'text-gray-600 hover:bg-gray-100 hover:text-blue-600') !!}>
                        <x-lucide-log-in class="h-5 w-5 shrink-0 {{ $isHeroVariant ? 'text-violet-300' : 'text-blue-500' }}" x-effect="swap($el, isHero, 'text-violet-300', 'text-blue-500')" aria-hidden="true" />
                        Login
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-base font-semibold bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg text-center transition-all duration-200"
                    >
                        <x-lucide-user-plus class="h-5 w-5 shrink-0" aria-hidden="true" />
                        Get Started
                    </a>
                @else
                    <a
                        href="{{ route('resumes.index') }}"
                        class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-base font-semibold bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg text-center transition-all duration-200"
                    >
                        <x-lucide-layout-dashboard class="h-5 w-5 shrink-0" aria-hidden="true" />
                        Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </nav>

    <main class="pt-16">
        {{ $slot }}
    </main>

    <footer class="bg-[#05060a] text-gray-300">
        <div class="border-y border-white/5 bg-gradient-to-r from-blue-600/10 via-purple-600/10 to-blue-600/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                    <div>
                        <p class="text-sm uppercase tracking-[0.35em] text-gray-400">Weekly insights</p>
                        <h3 class="text-2xl font-semibold text-white mt-2">Stay ahead with job market tactics.</h3>
                    </div>
                    <form x-data="newsletter" @submit.prevent="submit" class="md:col-span-2 flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <x-lucide-mail class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="email"
                                x-model="email"
                                placeholder="Enter your work email"
                                class="w-full bg-white/10 border border-white/20 rounded-2xl py-3 pl-12 pr-4 text-sm text-white placeholder:text-gray-400 focus:ring-2 focus:ring-blue-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                required
                                :disabled="subscribing || subscribed"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="subscribing || subscribed"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white text-[#05060a] font-semibold px-6 py-3 text-sm shadow-lg shadow-blue-500/20 hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                        >
                            <span class="inline-flex items-center gap-2" x-show="subscribing" x-cloak><x-lucide-loader-2 class="h-4 w-4 animate-spin" /> Subscribing...</span>
                            <span class="inline-flex items-center gap-2" x-show="!subscribing && subscribed" x-cloak><x-lucide-check class="h-4 w-4" /> Subscribed!</span>
                            <span class="inline-flex items-center gap-2" x-show="!subscribing && !subscribed">Subscribe <x-lucide-arrow-right class="h-4 w-4" /></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto py-14 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
                <div class="lg:col-span-2 space-y-5">
                    <div class="flex items-center space-x-2">
                        <img src="/logo-light.webp" width="40" height="40" alt="HResume Logo" loading="lazy" class="h-10 w-auto" />
                        <span class="text-2xl font-bold bg-gradient-to-r from-blue-300 to-purple-300 bg-clip-text text-transparent">HResume</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        Intelligent resume workflows for teams and talents. Build trust with ATS-friendly exports,
                        branded templates, and live collaboration that helps you sign offers faster.
                    </p>
                    <div class="flex items-center gap-4 text-sm text-gray-400">
                        <div>
                            <p class="text-white font-semibold text-lg">{{ number_format($resumeCount) }}</p>
                            <p>Resumes shipped</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 lg:col-span-2">
                    <div>
                        <h3 class="text-sm font-semibold text-white uppercase tracking-[0.2em]">Product</h3>
                        <ul class="mt-4 space-y-4 text-sm text-gray-400">
                            <li><a href="{{ url('/templates/public') }}" class="hover:text-white transition">Resume Templates</a></li>
                            <li><a href="{{ url('/cover-letter-builder') }}" class="hover:text-white transition">Cover Letter Builder</a></li>
                            <li><a href="{{ url('/work-certificate') }}" class="hover:text-white transition">Work Certificate</a></li>
                            <li><a href="{{ url('/pricing') }}" class="hover:text-white transition">Pricing</a></li>
                            <li><a href="{{ url('/blog') }}" class="hover:text-white transition">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white uppercase tracking-[0.2em]">Company</h3>
                        <ul class="mt-4 space-y-4 text-sm text-gray-400">
                            <li><a href="{{ url('/contact') }}" class="hover:text-white transition">Contact</a></li>
                            <li><a href="{{ url('/faq') }}" class="hover:text-white transition">FAQ</a></li>
                            <li><a href="{{ url('/terms') }}" class="hover:text-white transition">Terms</a></li>
                            <li><a href="{{ url('/privacy') }}" class="hover:text-white transition">Privacy</a></li>
                            <li><a href="{{ url('/refund') }}" class="hover:text-white transition">Refunds</a></li>
                            <li><a href="{{ url('/review') }}" class="hover:text-white transition">Reviews</a></li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-5">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-[0.2em]">Talk to humans</h3>
                    <div class="space-y-4 text-sm text-gray-400">
                        <a href="mailto:contact@hresume.pro" class="flex items-center gap-3 hover:text-white transition">
                            <x-lucide-mail class="h-4 w-4 text-blue-400" />
                            contact@hresume.pro
                        </a>
                        <a href="tel:+21692045389" class="flex items-center gap-3 hover:text-white transition">
                            <x-lucide-phone class="h-4 w-4 text-blue-400" />
                            +216 92 045 389
                        </a>
                        <div class="flex items-center gap-3">
                            <x-lucide-map-pin class="h-4 w-4 text-blue-400" />
                            Remote-first · Available worldwide
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <a href="https://linkedin.com" class="p-2 rounded-full bg-white/5 text-white hover:bg-white/10 transition" aria-label="LinkedIn"><x-lucide-linkedin class="h-4 w-4" /></a>
                        <a href="https://twitter.com" class="p-2 rounded-full bg-white/5 text-white hover:bg-white/10 transition" aria-label="Twitter"><x-lucide-twitter class="h-4 w-4" /></a>
                        <a href="https://github.com" class="p-2 rounded-full bg-white/5 text-white hover:bg-white/10 transition" aria-label="GitHub"><x-lucide-github class="h-4 w-4" /></a>
                    </div>
                </div>
            </div>

            <div class="mt-12 border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 gap-4">
                <p>© {{ date('Y') }} HResume. Built for ambitious careers.</p>
                <div class="flex items-center gap-6">
                    <a href="{{ url('/terms') }}" class="hover:text-white transition">Terms</a>
                    <a href="{{ url('/privacy') }}" class="hover:text-white transition">Privacy</a>
                    <a href="{{ url('/refund') }}" class="hover:text-white transition">Refunds</a>
                    <span class="text-gray-600">SOC2-ready infrastructure</span>
                </div>
            </div>
        </div>
    </footer>
</div>
</x-layouts.base>
