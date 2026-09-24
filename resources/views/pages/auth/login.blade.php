{{-- Port of pages/login.jsx --}}
<x-layouts.guest title="Sign in | HResume" robots="noindex, follow">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-lg" x-data="loginForm">
            <a href="{{ localized_url('/') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 transition-colors">
                <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                Back to Home
            </a>

            <div>
                <h2 class="mt-4 text-center text-3xl font-extrabold text-slate-900">Welcome Back</h2>
                <p class="mt-2 text-center text-sm text-slate-600">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign up</a>
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-md shadow-sm space-y-4">
                    <x-auth-input name="email" type="email" icon="user-check" label="Email address" placeholder="Email address" autocomplete="email" :show-error="true" />
                    <x-auth-input name="password" type="password" icon="lock" label="Password" placeholder="Password" autocomplete="current-password" />
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded" />
                        <label for="remember-me" class="ml-2 block text-sm text-slate-900">Remember me</label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Forgot your password?</a>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="group relative w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="submitting" x-cloak />
                        <span x-text="submitting ? 'Signing in...' : 'Sign in'">Sign in</span>
                    </button>
                </div>
            </form>

            <div class="relative mt-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-[11px] uppercase tracking-[0.3em]">
                    <span class="px-3 bg-white text-slate-400">Seamless access</span>
                </div>
            </div>

            <x-social-auth-button provider="linkedin" class="mt-4" title="Continue with LinkedIn" subtitle="Sign in with your LinkedIn profile" fallback="LinkedIn sign-in is temporarily unavailable." />
            <x-social-auth-button provider="google" class="mt-3" title="Continue with Google" subtitle="Use your professional Gmail in seconds" fallback="Google sign-in is temporarily unavailable." />
        </div>
    </div>
</x-layouts.guest>
