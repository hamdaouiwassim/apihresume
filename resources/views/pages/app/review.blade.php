{{-- Port of pages/Review.jsx --}}
@php
    $r = (array) t('review', [], []);
    $verified = (bool) auth()->user()->email_verified_at;
    $state = ['existing' => $existing, 'verified' => $verified, 'strings' => $r];
@endphp
<x-layouts.app :title="($r['title'] ?? 'Share Your Experience').' | HResume'">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12" x-data="reviewForm(@js($state))">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                    <x-lucide-heart class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $r['title'] ?? 'Share Your Experience' }}</h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $r['subtitle'] ?? "Your feedback helps us improve and helps others discover HResume. We'd love to hear about your experience!" }}</p>
            </div>

            <div class="mb-8 p-6 bg-green-50 border border-green-200 rounded-xl flex items-start gap-4" x-show="success" x-cloak>
                <x-lucide-check-circle class="h-6 w-6 text-green-600 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="text-green-800 font-semibold mb-1">{{ $r['successTitle'] ?? 'Thank You!' }}</h3>
                    <p class="text-green-700">{{ $r['successDescription'] ?? 'Your review has been submitted successfully. We appreciate your feedback!' }}</p>
                </div>
            </div>

            <div class="mb-8 p-6 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-4" x-show="existing && !success" @unless ($existing) x-cloak @endunless>
                <x-lucide-check-circle class="h-6 w-6 text-blue-600 flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <h3 class="text-blue-800 font-semibold mb-1">{{ $r['alreadySubmittedTitle'] ?? 'Review Already Submitted' }}</h3>
                    <p class="text-blue-700">{{ $r['alreadySubmittedMessage'] ?? 'You have already submitted a review. Thank you for your feedback!' }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
                <form @submit.prevent="submit" class="space-y-8">
                    <div>
                        <label class="block text-lg font-semibold text-gray-900 mb-4">
                            {{ $r['ratingLabel'] ?? 'How would you rate your experience?' }}
                            <span class="text-red-500" x-show="!existing">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            @for ($star = 1; $star <= 5; $star++)
                                <template x-if="existing">
                                    <x-lucide-star class="h-12 w-12" ::class="{{ $star }} <= form.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
                                </template>
                                <template x-if="!existing">
                                    <button type="button" @click="rate({{ $star }})" @mouseenter="hovered = {{ $star }}" @mouseleave="hovered = 0" class="focus:outline-none transition-transform hover:scale-110">
                                        <x-lucide-star class="h-12 w-12 transition-colors" ::class="{{ $star }} <= (hovered || form.rating) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
                                    </button>
                                </template>
                            @endfor
                            <span class="ml-4 text-lg font-semibold text-gray-700" x-show="form.rating > 0" x-text="ratingLabel"></span>
                        </div>
                        <p class="mt-2 text-sm text-red-600" x-show="errors.rating" x-cloak x-text="errors.rating"></p>
                    </div>

                    @unless ($verified)
                        <div class="p-6 bg-red-50 border border-red-200 rounded-xl mb-8">
                            <div class="flex items-start gap-4">
                                <x-lucide-shield-alert class="h-6 w-6 text-red-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h3 class="text-red-800 font-bold mb-1">{{ $r['verificationRequiredTitle'] ?? 'Email Verification Required' }}</h3>
                                    <p class="text-red-700 text-sm leading-relaxed">{{ $r['verificationRequiredMessage'] ?? 'To ensure the authenticity of our reviews, only verified users can share their experience. Please verify your email address to enable review submission.' }}</p>
                                </div>
                            </div>
                        </div>
                    @endunless

                    <div>
                        <label for="title" class="block text-lg font-semibold text-gray-900 mb-2">
                            {{ $r['titleLabel'] ?? 'Review Title' }} <span class="text-red-500" x-show="!existing">*</span>
                        </label>
                        <template x-if="existing">
                            <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700" x-text="form.title || @js($r['noTitle'] ?? 'No title provided')"></div>
                        </template>
                        <template x-if="!existing">
                            <div>
                                <input type="text" id="title" name="title" x-model="form.title" @input="clear('title')" maxlength="100"
                                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    :class="errors.title ? 'border-red-500' : 'border-gray-300'"
                                    placeholder="{{ $r['titlePlaceholder'] ?? 'e.g., Great platform for creating resumes' }}" />
                                <p class="mt-1 text-sm text-red-600" x-show="errors.title" x-text="errors.title"></p>
                                <p class="mt-1 text-sm text-gray-500" x-text="form.title.length + '/100 ' + @js($r['characters'] ?? 'characters')"></p>
                            </div>
                        </template>
                    </div>

                    <div>
                        <label for="comment" class="block text-lg font-semibold text-gray-900 mb-2">
                            {{ $r['commentLabel'] ?? 'Your Review' }} <span class="text-red-500" x-show="!existing">*</span>
                        </label>
                        <template x-if="existing">
                            <div class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 min-h-[150px] whitespace-pre-wrap" x-text="form.comment || @js($r['noComment'] ?? 'No comment provided')"></div>
                        </template>
                        <div x-show="!existing" @if ($existing) x-cloak @endif>
                            <textarea id="comment" name="comment" rows="6" x-model="form.comment" @input="clear('comment')"
                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                :class="errors.comment ? 'border-red-500' : 'border-gray-300'"
                                placeholder="{{ $r['commentPlaceholder'] ?? 'Tell us about your experience with HResume. What did you like? What could be improved?' }}"></textarea>
                            <div class="mt-2 flex justify-end">
                                <x-enhance-button value="form.comment" context="user review" />
                            </div>
                            <p class="mt-1 text-sm text-red-600" x-show="errors.comment" x-cloak x-text="errors.comment"></p>
                            <p class="mt-1 text-sm text-gray-500" x-text="form.comment.length + ' ' + @js($r['characters'] ?? 'characters') + ' (' + @js($r['minCharacters'] ?? 'minimum 10') + ')'"></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                        <template x-if="existing">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded border-2 flex items-center justify-center" :class="form.is_public ? 'bg-blue-600 border-blue-600' : 'border-gray-300'">
                                    <x-lucide-check-circle class="h-3 w-3 text-white" x-show="form.is_public" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900" x-text="form.is_public ? @js($r['publicStatus'] ?? 'This review is public') : @js($r['privateStatus'] ?? 'This review is private')"></p>
                                    <p class="text-sm text-gray-600 mt-1">{{ $r['publicDescription'] ?? 'Allow others to see your review on our platform. You can change this later.' }}</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="!existing">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="is_public" name="is_public" x-model="form.is_public" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                                <div>
                                    <label for="is_public" class="text-sm font-medium text-gray-900 cursor-pointer">{{ $r['publicLabel'] ?? 'Make this review public' }}</label>
                                    <p class="text-sm text-gray-600 mt-1">{{ $r['publicDescription'] ?? 'Allow others to see your review on our platform. You can change this later.' }}</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-4" x-show="!existing" @if ($existing) x-cloak @endif>
                        <button type="submit" :disabled="submitting || !verified"
                            class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold py-4 px-8 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2"
                            :class="submitting || !verified ? 'opacity-50 cursor-not-allowed transform-none shadow-none grayscale' : ''">
                            <x-lucide-loader-2 class="h-5 w-5 animate-spin" x-show="submitting" x-cloak />
                            <x-lucide-send class="h-5 w-5" x-show="!submitting" />
                            <span x-text="submitting ? @js($r['submitting'] ?? 'Submitting...') : @js($r['submitButton'] ?? 'Submit Review')">{{ $r['submitButton'] ?? 'Submit Review' }}</span>
                        </button>
                    </div>
                </form>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <x-lucide-thumbs-up class="h-6 w-6 text-blue-600 flex-shrink-0 mt-1" />
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $r['encouragementTitle'] ?? 'Your Review Matters!' }}</h3>
                                <p class="text-gray-700">{{ $r['encouragementText'] ?? "By sharing your experience, you're helping us improve HResume and helping other job seekers discover a great tool for creating professional resumes. Thank you for taking the time!" }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
