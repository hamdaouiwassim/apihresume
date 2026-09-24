{{-- Port of pages/Profile.jsx --}}
@php
    $p = (array) t('profile', [], []);
    $g = (array) ($p['githubImport'] ?? []);
    $f = (array) ($p['fields'] ?? []);
    $pw = (array) ($p['password'] ?? []);
    $av = (array) ($p['avatar'] ?? []);
    $btn = (array) ($p['buttons'] ?? []);
    $user = auth()->user();
    $state = [
        'user' => ['name' => $user->name, 'email' => $user->email, 'avatar' => $user->avatar],
        'strings' => [
            'validation' => $p['validation'] ?? [],
            'notifications' => $p['notifications'] ?? [],
            'githubImport' => $g,
        ],
    ];
    $inputBase = 'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors';
    $errOn = 'border-red-300 bg-red-50';
@endphp
<x-layouts.app :title="($p['title'] ?? 'Profile Settings').' | HResume'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12" x-data="profileForm(@js($state))">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $p['title'] ?? 'Profile Settings' }}</h1>
            <p class="text-gray-600">{{ $p['subtitle'] ?? 'Manage your account information and preferences' }}</p>
        </div>

        <x-ai-token-credits class="mb-6" />

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <form @submit.prevent="submit">
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-8 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                        <div class="relative">
                            <img :src="form.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=default'" src="{{ user_avatar($user) }}" alt="Profile" class="h-24 w-24 rounded-full border-4 border-white shadow-lg object-cover" />
                            <label for="avatar-upload" class="absolute bottom-0 right-0 p-2 bg-blue-600 text-white rounded-full cursor-pointer hover:bg-blue-700 transition-colors shadow-lg" title="{{ $av['changeTooltip'] ?? 'Change avatar' }}">
                                <x-lucide-camera class="h-4 w-4" />
                                <input id="avatar-upload" type="file" accept="image/*" @change="onAvatar($event)" class="hidden" />
                            </label>
                        </div>
                        <div class="text-center sm:text-left flex-1">
                            <h2 class="text-xl font-semibold text-gray-900 mb-1" x-text="form.name || @js($av['nameFallback'] ?? 'Your Name')">{{ $user->name }}</h2>
                            <p class="text-gray-600 text-sm mb-2" x-text="form.email">{{ $user->email }}</p>
                            <label for="avatar-upload" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 cursor-pointer font-medium">
                                <x-lucide-camera class="h-4 w-4 mr-1" />
                                {{ $av['changeCta'] ?? 'Change Profile Picture' }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6 border-b border-gray-200 bg-slate-50/90">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex flex-col gap-2">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <x-lucide-github class="h-5 w-5 text-gray-800" aria-hidden="true" />
                                {{ $g['title'] ?? 'GitHub (resume projects)' }}
                            </h3>
                            <p class="text-sm text-gray-600 max-w-xl">{{ $g['description'] ?? 'Connect GitHub to import from private repositories you can access.' }}</p>
                            <template x-if="user?.github_import_connected && user?.github_import_login">
                                <p class="text-sm text-gray-800"><span class="font-medium">{{ $g['connectedAs'] ?? 'Connected as' }}</span> <span x-text="'@' + user.github_import_login"></span></p>
                            </template>
                        </div>
                        <div class="flex flex-col sm:items-end gap-2 shrink-0">
                            <button type="button" x-show="user?.github_import_connected" @if (! $user->github_import_connected) x-cloak @endif @click="disconnectGithub()" :disabled="githubLoading"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <x-lucide-loader-2 class="h-4 w-4 mr-2 animate-spin" x-show="githubLoading" x-cloak />
                                <span x-text="githubLoading ? @js($g['disconnecting'] ?? 'Disconnecting…') : @js($g['disconnect'] ?? 'Disconnect')">{{ $g['disconnect'] ?? 'Disconnect' }}</span>
                            </button>
                            <button type="button" x-show="!user?.github_import_connected" @if ($user->github_import_connected) x-cloak @endif @click="connectGithub()" :disabled="githubLoading"
                                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                                <x-lucide-loader-2 class="h-4 w-4 mr-2 animate-spin" x-show="githubLoading" x-cloak />
                                <x-lucide-github class="h-4 w-4 mr-2" x-show="!githubLoading" aria-hidden="true" />
                                <span x-text="githubLoading ? @js($g['connecting'] ?? 'Redirecting…') : @js($g['connect'] ?? 'Connect GitHub')">{{ $g['connect'] ?? 'Connect GitHub' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    @foreach ([['name', 'text', 'user', $f['name']['label'] ?? 'Full Name', $f['name']['placeholder'] ?? 'Enter your full name'], ['email', 'email', 'mail', $f['email']['label'] ?? 'Email Address', $f['email']['placeholder'] ?? 'Enter your email']] as [$field, $type, $icon, $label, $placeholder])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 mb-2">
                                <x-dynamic-component :component="'lucide-'.$icon" class="h-4 w-4 inline mr-1" />
                                {{ $label }}
                            </label>
                            <input type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" x-model="form.{{ $field }}" @input="touch('{{ $field }}')"
                                class="{{ $inputBase }} border-gray-300" x-effect="$swap($el, !!errors.{{ $field }}, '{{ $errOn }}', 'border-gray-300')" placeholder="{{ $placeholder }}" />
                            <p x-show="errors.{{ $field }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $field }} && errors.{{ $field }}[0]"></p>
                        </div>
                    @endforeach

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <x-lucide-lock class="h-5 w-5 mr-2" />
                            {{ $pw['sectionTitle'] ?? 'Change Password' }}
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">{{ $pw['helper'] ?? "Leave blank if you don't want to change your password" }}</p>

                        @foreach ([['password', 'showPassword', $pw['newLabel'] ?? 'New Password', $pw['newPlaceholder'] ?? 'Enter new password', 'mb-4'], ['password_confirmation', 'showConfirm', $pw['confirmLabel'] ?? 'Confirm New Password', $pw['confirmPlaceholder'] ?? 'Confirm new password', '']] as [$field, $toggle, $label, $placeholder, $wrap])
                            <div class="{{ $wrap }}">
                                <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
                                <div class="relative">
                                    <input :type="{{ $toggle }} ? 'text' : 'password'" type="password" id="{{ $field }}" name="{{ $field }}" x-model="form.{{ $field }}" @input="touch('{{ $field }}')"
                                        class="{{ $inputBase }} pr-10 border-gray-300" x-effect="$swap($el, !!errors.{{ $field }}, '{{ $errOn }}', 'border-gray-300')" placeholder="{{ $placeholder }}" />
                                    <button type="button" @click="{{ $toggle }} = !{{ $toggle }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                        <x-lucide-eye-off class="h-5 w-5" x-show="{{ $toggle }}" x-cloak />
                                        <x-lucide-eye class="h-5 w-5" x-show="!{{ $toggle }}" />
                                    </button>
                                </div>
                                <p x-show="errors.{{ $field }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $field }} && errors.{{ $field }}[0]"></p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex items-center text-sm text-gray-600">
                        <span class="flex items-center text-blue-600" x-show="changed" x-cloak>
                            <x-lucide-check-circle class="h-4 w-4 mr-1" />
                            {{ $p['status']['unsaved'] ?? 'You have unsaved changes' }}
                        </span>
                    </div>
                    <button type="submit" :disabled="blocked"
                        class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200 bg-gray-300 text-gray-500 cursor-not-allowed"
                        x-effect="$swap($el, blocked, 'bg-gray-300 text-gray-500 cursor-not-allowed', 'bg-gradient-to-r from-blue-500 to-purple-600 text-white hover:from-blue-600 hover:to-purple-700 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5')">
                        <x-lucide-loader-2 class="h-4 w-4 mr-2 animate-spin" x-show="saving" x-cloak />
                        <x-lucide-save class="h-4 w-4 mr-2" x-show="!saving" />
                        <span x-text="saving ? @js($btn['saving'] ?? 'Saving...') : @js($btn['save'] ?? 'Save Changes')">{{ $btn['save'] ?? 'Save Changes' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
