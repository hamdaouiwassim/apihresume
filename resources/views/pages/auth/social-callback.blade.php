{{-- Port of pages/SocialCallback.jsx --}}
<x-layouts.guest title="Signing you in | HResume" robots="noindex, nofollow">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4" x-data="socialCallback(@js($error ?? null))">
        <div class="max-w-md w-full bg-white shadow-xl rounded-3xl p-10 text-center space-y-6" x-show="status === 'processing'">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <x-lucide-loader-2 class="h-8 w-8 animate-spin" />
            </div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400">Signing you in</p>
            <h1 class="text-2xl font-semibold text-slate-900">Please hold tight</h1>
            <p class="text-slate-600" x-text="message">Connecting to your account...</p>
        </div>

        <div class="max-w-md w-full bg-white shadow-xl rounded-3xl p-10 text-center space-y-6" x-show="status === 'error'" x-cloak>
            <div class="mx-auto w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <x-lucide-alert-circle class="h-8 w-8" />
            </div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400">Social login error</p>
            <h1 class="text-2xl font-semibold text-slate-900">We couldn’t finish signing in</h1>
            <p class="text-slate-600" x-text="message"></p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 text-white py-3 font-semibold">
                    <x-lucide-shield-check class="h-5 w-5" />
                    Try again
                </a>
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                    <x-lucide-home class="h-5 w-5" />
                    Back to home
                </a>
            </div>
        </div>
    </div>
</x-layouts.guest>
