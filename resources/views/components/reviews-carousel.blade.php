@props(['reviews'])
{{-- Port of components/ReviewsCarousel.jsx (reviews rendered server-side, carousel via Alpine) --}}
@if ($reviews->isNotEmpty())
<div class="mt-20 py-16 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 w-full" x-data="reviewsCarousel({{ $reviews->count() }})">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ t('welcome.reviews.title', [], 'What Our Users Say') }}</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ t('welcome.reviews.subtitle', [], "Don't just take our word for it. See what our users have to say about their experience with HResume.") }}</p>
        </div>

        <div class="relative max-w-7xl mx-auto">
            <div class="relative overflow-hidden rounded-2xl">
                <div
                    class="flex py-2 transition-transform duration-500 ease-in-out touch-pan-y"
                    :style="trackStyle"
                    @touchstart="onTouchStart($event)"
                    @touchmove="onTouchMove($event)"
                    @touchend="onTouchEnd()"
                >
                    @foreach ($reviews as $review)
                        <div class="flex-shrink-0 px-2 sm:px-3 md:px-4 w-1/3" x-effect="$el.classList.remove('w-full', 'w-1/2', 'w-1/3'); $el.classList.add(cardClass)">
                            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 h-full p-4 sm:p-6 md:p-8 flex flex-col">
                                <div class="flex justify-center mb-3 sm:mb-4">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                        <x-lucide-quote class="h-5 w-5 sm:h-6 sm:w-6 md:h-7 md:w-7 text-white" />
                                    </div>
                                </div>
                                <div class="flex justify-center gap-0.5 sm:gap-1 mb-3 sm:mb-4">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <x-lucide-star @class([
                                            'h-4 w-4 sm:h-5 sm:w-5',
                                            'fill-yellow-400 text-yellow-400' => $star <= $review->rating,
                                            'text-gray-300' => $star > $review->rating,
                                        ]) />
                                    @endfor
                                </div>
                                @if ($review->title)
                                    <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 mb-2 sm:mb-3 text-center line-clamp-2">{{ $review->title }}</h3>
                                @endif
                                <p class="text-sm sm:text-base text-gray-700 leading-relaxed mb-4 sm:mb-6 flex-grow line-clamp-4 sm:line-clamp-5 text-center">"{{ $review->comment }}"</p>
                                <div class="flex items-center justify-center gap-2 sm:gap-3 mt-auto">
                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm sm:text-base flex-shrink-0">
                                        {{ mb_strtoupper(mb_substr($review->user?->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="text-left min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <p class="font-semibold text-gray-900 text-xs sm:text-sm truncate">{{ $review->user?->name ?? 'Anonymous' }}</p>
                                            <x-lucide-badge-check class="h-4 w-4 sm:h-5 sm:w-5 text-blue-600 flex-shrink-0" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template x-if="totalSlides > 1">
                    <div>
                        <button type="button" @click="prev(); pauseBriefly()" class="absolute left-1 sm:left-2 md:left-4 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-14 sm:h-14 md:w-12 md:h-12 rounded-full bg-white shadow-lg hover:shadow-xl active:scale-95 flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all duration-200 z-10 touch-manipulation" aria-label="Previous review">
                            <x-lucide-chevron-left class="h-6 w-6 sm:h-7 sm:w-7 md:h-6 md:w-6" />
                        </button>
                        <button type="button" @click="next(); pauseBriefly()" class="absolute right-1 sm:right-2 md:right-4 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-14 sm:h-14 md:w-12 md:h-12 rounded-full bg-white shadow-lg hover:shadow-xl active:scale-95 flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all duration-200 z-10 touch-manipulation" aria-label="Next review">
                            <x-lucide-chevron-right class="h-6 w-6 sm:h-7 sm:w-7 md:h-6 md:w-6" />
                        </button>
                        <div class="py-2 absolute bottom-4 sm:bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-10 px-2">
                            <template x-for="i in totalSlides" :key="i">
                                <button type="button" @click="go(i - 1)"
                                    class="h-2 sm:h-2.5 rounded-full transition-all duration-300 touch-manipulation"
                                    :class="i - 1 === index ? 'bg-blue-600 w-8 sm:w-10' : 'bg-gray-300 hover:bg-gray-400 w-2 sm:w-2.5'"
                                    :aria-label="'Go to slide ' + i"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4 text-sm sm:text-base">{{ t('welcome.reviews.ctaText', [], 'Share your experience with HResume') }}</p>
                <a href="{{ route('review') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    {{ t('welcome.reviews.ctaButton', [], 'Leave a Review') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endif
