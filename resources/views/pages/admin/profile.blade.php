{{-- Port of pages/admin/AdminProfile.jsx (same save logic as the user profile: profileForm) --}}
@php
    $user = auth()->user();
    $state = [
        'user' => ['name' => $user->name, 'email' => $user->email, 'avatar' => $user->avatar],
        'strings' => ['notifications' => ['updateSuccess' => 'Profile updated successfully!', 'updateError' => 'Failed to update profile']],
    ];
    $inputBase = 'w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors';
    $errOn = 'border-red-300 bg-red-50';
@endphp
<x-layouts.admin title="Profile | Admin | HResume">
    <div class="max-w-4xl mx-auto" x-data="profileForm(@js($state))">
        <div class="mb-8 animate-slide-in">
            <div class="flex items-center gap-2 mb-2">
                <x-lucide-shield class="h-6 w-6 text-purple-600 animate-pulse-slow" />
                <span class="text-purple-600 font-semibold">Administrator Profile</span>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Profile Settings</h1>
            <p class="text-gray-600">Manage your account information and preferences</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <form @submit.prevent="submit">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-8 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6">
                        <div class="relative">
                            <img :src="form.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=default'" src="{{ user_avatar($user) }}" alt="Profile" class="h-24 w-24 rounded-full border-4 border-white shadow-lg object-cover" />
                            <label for="avatar-upload" class="absolute bottom-0 right-0 p-2 bg-purple-600 text-white rounded-full cursor-pointer hover:bg-purple-700 transition-colors shadow-lg" title="Change avatar">
                                <x-lucide-camera class="h-4 w-4" />
                                <input id="avatar-upload" type="file" accept="image/*" @change="onAvatar($event)" class="hidden" />
                            </label>
                        </div>
                        <div class="text-center sm:text-left flex-1">
                            <div class="flex items-center justify-center sm:justify-start gap-2 mb-2">
                                <h2 class="text-xl font-semibold text-gray-900" x-text="form.name || 'Your Name'">{{ $user->name }}</h2>
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold flex items-center gap-1"><x-lucide-shield class="h-3 w-3" /> Admin</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-2" x-text="form.email">{{ $user->email }}</p>
                            <label for="avatar-upload" class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 cursor-pointer font-medium">
                                <x-lucide-camera class="h-4 w-4 mr-1" /> Change Profile Picture
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    @foreach ([['name', 'text', 'user', 'Full Name', 'Enter your full name'], ['email', 'email', 'mail', 'Email Address', 'Enter your email address']] as [$field, $type, $icon, $label, $placeholder])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 mb-2">
                                <x-dynamic-component :component="'lucide-'.$icon" class="h-4 w-4 inline mr-1" /> {{ $label }}
                            </label>
                            <input type="{{ $type }}" id="{{ $field }}" x-model="form.{{ $field }}" @input="touch('{{ $field }}')" class="{{ $inputBase }} border-gray-300"
                                x-effect="$swap($el, !!errors.{{ $field }}, '{{ $errOn }}', 'border-gray-300')" placeholder="{{ $placeholder }}" />
                            <p x-show="errors.{{ $field }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $field }} && errors.{{ $field }}[0]"></p>
                        </div>
                    @endforeach

                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
                        <div class="space-y-6">
                            @foreach ([['password', 'showPassword', 'New Password', 'Leave blank to keep current password', true], ['password_confirmation', 'showConfirm', 'Confirm New Password', 'Confirm your new password', false]] as [$field, $toggle, $label, $placeholder, $icon])
                                <div>
                                    <label for="{{ $field }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        @if ($icon)<x-lucide-lock class="h-4 w-4 inline mr-1" />@endif {{ $label }}
                                    </label>
                                    <div class="relative">
                                        <input :type="{{ $toggle }} ? 'text' : 'password'" type="password" id="{{ $field }}" x-model="form.{{ $field }}" @input="touch('{{ $field }}')"
                                            class="{{ $inputBase }} pr-10 border-gray-300" x-effect="$swap($el, !!errors.{{ $field }}, '{{ $errOn }}', 'border-gray-300')" placeholder="{{ $placeholder }}" />
                                        <button type="button" @click="{{ $toggle }} = !{{ $toggle }}" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <x-lucide-eye-off class="h-5 w-5" x-show="{{ $toggle }}" x-cloak />
                                            <x-lucide-eye class="h-5 w-5" x-show="!{{ $toggle }}" />
                                        </button>
                                    </div>
                                    <p x-show="errors.{{ $field }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $field }} && errors.{{ $field }}[0]"></p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button type="button" @click="reset()" :disabled="!changed || saving" class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                        <button type="submit" :disabled="blocked" class="flex items-center px-6 py-2.5 rounded-lg font-semibold transition-all duration-200 shadow-lg hover:shadow-xl bg-gray-400 text-white cursor-not-allowed"
                            x-effect="$swap($el, blocked, 'bg-gray-400 text-white cursor-not-allowed', 'bg-gradient-to-r from-purple-500 to-pink-600 text-white hover:from-purple-600 hover:to-pink-700')">
                            <x-lucide-loader-2 class="h-4 w-4 mr-2 animate-spin" x-show="saving" x-cloak />
                            <x-lucide-save class="h-4 w-4 mr-2" x-show="!saving" />
                            <span x-text="saving ? @js(t('common.loading', [], 'Loading...')) : @js(t('common.save', [], 'Save'))">{{ t('common.save', [], 'Save') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
