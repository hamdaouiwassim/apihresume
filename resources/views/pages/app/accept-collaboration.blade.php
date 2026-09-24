{{-- Port of pages/AcceptCollaboration.jsx (AuthLayout when signed in, GuestLayout otherwise) --}}
<x-dynamic-component :component="auth()->check() ? 'layouts.app' : 'layouts.guest'" title="Accept invitation | HResume" robots="noindex, nofollow">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" x-data="acceptCollaboration(@js($token))">
        <div class="max-w-md w-full space-y-8">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div x-show="status === 'loading'">
                    <x-lucide-loader-2 class="h-12 w-12 animate-spin text-blue-600 mx-auto mb-4" />
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Accepting Invitation...</h2>
                    <p class="text-gray-600">Please wait while we process your invitation.</p>
                </div>
                <div x-show="status === 'success'" x-cloak>
                    <x-lucide-check-circle class="h-12 w-12 text-green-600 mx-auto mb-4" />
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Invitation Accepted!</h2>
                    <p class="text-gray-600 mb-4" x-text="message"></p>
                </div>
                <div x-show="status === 'error'" x-cloak>
                    <x-lucide-x-circle class="h-12 w-12 text-red-600 mx-auto mb-4" />
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Unable to Accept Invitation</h2>
                    <p class="text-gray-600 mb-4" x-text="message"></p>
                    <a href="{{ route('login') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
