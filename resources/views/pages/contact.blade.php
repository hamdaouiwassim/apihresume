{{-- Port of pages/ContactUs.jsx --}}
@php
    $validation = [
        'nameRequired' => t('contact.validation.nameRequired', [], 'Name is required'),
        'emailRequired' => t('contact.validation.emailRequired', [], 'Email is required'),
        'emailInvalid' => t('contact.validation.emailInvalid', [], 'Please enter a valid email address'),
        'subjectRequired' => t('contact.validation.subjectRequired', [], 'Subject is required'),
        'messageRequired' => t('contact.validation.messageRequired', [], 'Message is required'),
        'messageMinLength' => t('contact.validation.messageMinLength', [], 'Message must be at least 10 characters'),
    ];
    $fields = [
        ['name', 'text', t('contact.form.nameLabel', [], 'Name'), t('contact.form.namePlaceholder', [], 'Your full name')],
        ['email', 'email', t('contact.form.emailLabel', [], 'Email'), t('contact.form.emailPlaceholder', [], 'your.email@example.com')],
        ['subject', 'text', t('contact.form.subjectLabel', [], 'Subject'), t('contact.form.subjectPlaceholder', [], 'What is this regarding?')],
    ];
    $inputBase = 'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors';
@endphp
<x-layouts.guest
    :title="t('contact.title', [], 'Contact Us').' | HResume'"
    :description="t('contact.subtitle', [], 'Have a question or need help? We\'re here to assist you.')"
    canonical="/contact"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-mail class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ t('contact.title', [], 'Contact Us') }}</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ t('contact.subtitle', [], "Have a question or need help? We're here to assist you. Send us a message and we'll get back to you as soon as possible.") }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ t('contact.info.title', [], 'Get in Touch') }}</h2>
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <x-lucide-mail class="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">{{ t('contact.info.emailLabel', [], 'Email') }}</h3>
                                    <p class="text-gray-600">{{ t('contact.info.email', [], 'contact@hresume.pro') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <x-lucide-phone class="h-6 w-6 text-purple-600" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">{{ t('contact.info.phoneLabel', [], 'Phone') }}</h3>
                                    <p class="text-gray-600">{{ t('contact.info.phone', [], '+216 92 045 389') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                    <x-lucide-map-pin class="h-6 w-6 text-green-600" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">{{ t('contact.info.addressLabel', [], 'Address') }}</h3>
                                    <p class="text-gray-600">{{ t('contact.info.address', [], 'Skanes, Monastir - Tunisia') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <p class="text-sm text-gray-600">{{ t('contact.info.responseTime', [], 'We typically respond within 24-48 hours during business days.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-xl p-8" x-data="contactForm(@js($validation))">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ t('contact.form.title', [], 'Send us a Message') }}</h2>

                        <div x-show="status === 'success'" x-cloak class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-green-800">{{ t('contact.form.successMessage', [], "Thank you! Your message has been sent successfully. We'll get back to you soon.") }}</p>
                        </div>
                        <div x-show="status === 'error'" x-cloak class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-800">{{ t('contact.form.errorMessage', [], 'Oops! Something went wrong. Please try again later.') }}</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6" novalidate>
                            @foreach ($fields as [$name, $type, $label, $placeholder])
                                <div>
                                    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">{{ $label }} <span class="text-red-500">*</span></label>
                                    <input
                                        type="{{ $type }}"
                                        id="{{ $name }}"
                                        name="{{ $name }}"
                                        x-model="form.{{ $name }}"
                                        @input="clearError('{{ $name }}')"
                                        class="{{ $inputBase }} border-gray-300"
                                        x-effect="$swap($el, !!errors.{{ $name }}, 'border-red-500', 'border-gray-300')"
                                        placeholder="{{ $placeholder }}"
                                    />
                                    <p x-show="errors.{{ $name }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $name }}"></p>
                                </div>
                            @endforeach

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">{{ t('contact.form.messageLabel', [], 'Message') }} <span class="text-red-500">*</span></label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="6"
                                    x-model="form.message"
                                    @input="clearError('message')"
                                    class="{{ $inputBase }} resize-none border-gray-300"
                                    x-effect="$swap($el, !!errors.message, 'border-red-500', 'border-gray-300')"
                                    placeholder="{{ t('contact.form.messagePlaceholder', [], 'Tell us how we can help you...') }}"
                                ></textarea>
                                <div class="mt-2 flex justify-end">
                                    <x-enhance-button value="form.message" context="contact message" />
                                </div>
                                <p x-show="errors.message" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.message"></p>
                            </div>

                            <button
                                type="submit"
                                :disabled="submitting"
                                class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2"
                                :class="submitting ? 'opacity-50 cursor-not-allowed' : ''"
                            >
                                <span class="inline-flex items-center gap-2" x-show="submitting" x-cloak>
                                    <span class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    {{ t('contact.form.submitting', [], 'Sending...') }}
                                </span>
                                <span class="inline-flex items-center gap-2" x-show="!submitting">
                                    <x-lucide-send class="h-5 w-5" />
                                    {{ t('contact.form.submitButton', [], 'Send Message') }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
