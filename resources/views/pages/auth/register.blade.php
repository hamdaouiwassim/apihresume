{{-- Port of pages/register.jsx --}}
@php $next = request('next'); @endphp
<x-layouts.guest title="Create your account | HResume" description="Create a free HResume account to build ATS-friendly resumes, cover letters and work certificates." canonical="/register">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-lg" x-data="registerForm">
            <a href="{{ url('/') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 transition-colors">
                <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                Back to Home
            </a>

            <div>
                <h2 class="mt-4 text-center text-3xl font-extrabold text-slate-900">Create your account</h2>
                <p class="mt-2 text-center text-sm text-slate-600">
                    Already have an account?
                    <a href="{{ $next ? route('login').'?next='.rawurlencode($next) : route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-md shadow-sm space-y-4">
                    <x-auth-input name="name" icon="user-check" label="Full Name" placeholder="Full Name" />
                    <x-auth-input name="email" type="email" icon="mail" label="Email address" placeholder="Email address" autocomplete="email" :show-error="true" />
                    <x-auth-input name="password" type="password" icon="lock" label="Password" placeholder="Password" :show-error="true" />
                    <x-auth-input name="password_confirmation" type="password" icon="lock" label="Confirm Password" placeholder="Confirm Password" />
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="group relative w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-300 ease-in-out transform hover:-translate-y-1 disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="submitting" x-cloak />
                        <span x-text="submitting ? 'Creating Account...' : 'Create Account'">Create Account</span>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-[11px] uppercase tracking-[0.3em]">
                        <span class="px-3 bg-white text-gray-400">Instant onboarding</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <x-social-auth-button provider="linkedin" border="border-gray-200" title="Sign up with LinkedIn" subtitle="Use your LinkedIn identity in one tap" fallback="LinkedIn sign-up is temporarily unavailable." />
                <x-social-auth-button provider="google" border="border-gray-200" title="Sign up with Google" subtitle="Create your account in one tap" fallback="Google sign-up is temporarily unavailable." />
            </div>
        </div>
    </div>
</x-layouts.guest>
