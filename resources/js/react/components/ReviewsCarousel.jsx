import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Star, ChevronLeft, ChevronRight, Quote, BadgeCheck } from 'lucide-react';
import { getAllReviews } from '../services/reviewService';
import { useLanguage } from '../context/LanguageContext';

export default function ReviewsCarousel() {
  const { t } = useLanguage();
  const reviews = t?.welcome?.reviews || {};
  const [reviewList, setReviewList] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [touchStart, setTouchStart] = useState(null);
  const [touchEnd, setTouchEnd] = useState(null);
  const [isPaused, setIsPaused] = useState(false);
  const [cardsPerSlide, setCardsPerSlide] = useState(3);

  // Minimum swipe distance (in px)
  const minSwipeDistance = 50;

  // Calculate cards per slide based on screen size
  useEffect(() => {
    const updateCardsPerSlide = () => {
      if (window.innerWidth < 640) {
        setCardsPerSlide(1); // Mobile: 1 card
      } else if (window.innerWidth < 1024) {
        setCardsPerSlide(2); // Tablet: 2 cards
      } else {
        setCardsPerSlide(3); // Desktop: 3 cards
      }
    };

    updateCardsPerSlide();
    window.addEventListener('resize', updateCardsPerSlide);
    return () => window.removeEventListener('resize', updateCardsPerSlide);
  }, []);

  // Calculate total number of slides
  const totalSlides = Math.ceil(reviewList.length / cardsPerSlide);

  useEffect(() => {
    fetchReviews();
  }, []);

  const fetchReviews = async () => {
    try {
      setIsLoading(true);
      const response = await getAllReviews({ public_only: true, per_page: 10 });
      if (response.data.status && response.data.data) {
        const reviews = Array.isArray(response.data.data) 
          ? response.data.data 
          : (response.data.data.data || []);
        // Filter for public reviews only and ensure they have required fields
        const publicReviews = reviews.filter(review => 
          review.is_public && 
          review.rating && 
          review.comment && 
          review.user
        );
        setReviewList(publicReviews);
      }
    } catch (error) {
      // Silently fail - don't show carousel if there's an error
    } finally {
      setIsLoading(false);
    }
  };

  const nextReview = () => {
    setCurrentIndex((prev) => (prev + 1) % totalSlides);
  };

  const prevReview = () => {
    setCurrentIndex((prev) => (prev - 1 + totalSlides) % totalSlides);
  };

  const goToReview = (index) => {
    setCurrentIndex(index);
  };

  // Touch handlers for swipe
  const onTouchStart = (e) => {
    setTouchEnd(null);
    setTouchStart(e.targetTouches[0].clientX);
    setIsPaused(true);
  };

  const onTouchMove = (e) => {
    setTouchEnd(e.targetTouches[0].clientX);
  };

  const onTouchEnd = () => {
    if (!touchStart || !touchEnd) return;
    
    const distance = touchStart - touchEnd;
    const isLeftSwipe = distance > minSwipeDistance;
    const isRightSwipe = distance < -minSwipeDistance;

    if (isLeftSwipe) {
      nextReview();
    } else if (isRightSwipe) {
      prevReview();
    }

    // Resume auto-play after 3 seconds
    setTimeout(() => setIsPaused(false), 3000);
  };

  // Auto-play carousel (paused when user interacts)
  useEffect(() => {
    if (totalSlides > 1 && !isPaused) {
      const interval = setInterval(() => {
        setCurrentIndex((prev) => (prev + 1) % totalSlides);
      }, 5000); // Change slide every 5 seconds
      return () => clearInterval(interval);
    }
  }, [totalSlides, isPaused]);

  if (isLoading) {
    return null; // Don't show anything while loading
  }

  if (reviewList.length === 0) {
    return null; // Don't show carousel if no reviews
  }

  return (
    <div className="mt-20 py-16 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 w-full">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {reviews.title || "What Our Users Say"}
          </h2>
          <p className="text-lg text-gray-600 max-w-2xl mx-auto">
            {reviews.subtitle || "Don't just take our word for it. See what our users have to say about their experience with HResume."}
          </p>
        </div>

        {/* Carousel Container */}
        <div className="relative max-w-7xl mx-auto">
          {/* Reviews Carousel */}
          <div className="relative overflow-hidden rounded-2xl">
            <div 
              className="flex py-2 transition-transform duration-500 ease-in-out touch-pan-y"
              style={{ 
                transform: `translateX(-${currentIndex * (100 / cardsPerSlide)}%)`,
                willChange: 'transform'
              }}
              onTouchStart={onTouchStart}
              onTouchMove={onTouchMove}
              onTouchEnd={onTouchEnd}
            >
              {reviewList.map((review, index) => (
                <div
                  key={review.id}
                  className={`flex-shrink-0 px-2 sm:px-3 md:px-4 ${
                    cardsPerSlide === 1 ? 'w-full' :
                    cardsPerSlide === 2 ? 'w-1/2' :
                    'w-1/3'
                  }`}
                >
                  <div className="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 h-full p-4 sm:p-6 md:p-8 flex flex-col">
                    {/* Quote Icon */}
                    <div className="flex justify-center mb-3 sm:mb-4">
                      <div className="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <Quote className="h-5 w-5 sm:h-6 sm:w-6 md:h-7 md:w-7 text-white" />
                      </div>
                    </div>

                    {/* Rating Stars */}
                    <div className="flex justify-center gap-0.5 sm:gap-1 mb-3 sm:mb-4">
                      {[1, 2, 3, 4, 5].map((star) => (
                        <Star
                          key={star}
                          className={`h-4 w-4 sm:h-5 sm:w-5 ${
                            star <= review.rating
                              ? 'fill-yellow-400 text-yellow-400'
                              : 'text-gray-300'
                          }`}
                        />
                      ))}
                    </div>

                    {/* Review Title */}
                    {review.title && (
                      <h3 className="text-base sm:text-lg md:text-xl font-bold text-gray-900 mb-2 sm:mb-3 text-center line-clamp-2">
                        {review.title}
                      </h3>
                    )}

                    {/* Review Comment */}
                    <p className="text-sm sm:text-base text-gray-700 leading-relaxed mb-4 sm:mb-6 flex-grow line-clamp-4 sm:line-clamp-5 text-center">
                      "{review.comment}"
                    </p>

                    {/* Reviewer Info */}
                    <div className="flex items-center justify-center gap-2 sm:gap-3 mt-auto">
                      <div className="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm sm:text-base flex-shrink-0">
                        {review.user?.name?.charAt(0)?.toUpperCase() || 'U'}
                      </div>
                      <div className="text-left min-w-0 flex-1">
                        <div className="flex items-center gap-1.5">
                          <p className="font-semibold text-gray-900 text-xs sm:text-sm truncate">
                            {review.user?.name || 'Anonymous'}
                          </p>
                          <BadgeCheck className="h-4 w-4 sm:h-5 sm:w-5 text-blue-600 flex-shrink-0" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Navigation Arrows */}
            {totalSlides > 1 && (
              <>
                <button
                  onClick={() => {
                    prevReview();
                    setIsPaused(true);
                    setTimeout(() => setIsPaused(false), 3000);
                  }}
                  className="absolute left-1 sm:left-2 md:left-4 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-14 sm:h-14 md:w-12 md:h-12 rounded-full bg-white shadow-lg hover:shadow-xl active:scale-95 flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all duration-200 z-10 touch-manipulation"
                  aria-label="Previous review"
                >
                  <ChevronLeft className="h-6 w-6 sm:h-7 sm:w-7 md:h-6 md:w-6" />
                </button>
                <button
                  onClick={() => {
                    nextReview();
                    setIsPaused(true);
                    setTimeout(() => setIsPaused(false), 3000);
                  }}
                  className="absolute right-1 sm:right-2 md:right-4 top-1/2 -translate-y-1/2 w-12 h-12 sm:w-14 sm:h-14 md:w-12 md:h-12 rounded-full bg-white shadow-lg hover:shadow-xl active:scale-95 flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all duration-200 z-10 touch-manipulation"
                  aria-label="Next review"
                >
                  <ChevronRight className="h-6 w-6 sm:h-7 sm:w-7 md:h-6 md:w-6" />
                </button>
              </>
            )}

            {/* Dots Indicator */}
            {totalSlides > 1 && (
              <div className="py-2 absolute bottom-4 sm:bottom-6 left-1/2 -translate-x-1/2 flex gap-2 z-10 px-2">
                {Array.from({ length: totalSlides }).map((_, index) => (
                  <button
                    key={index}
                    onClick={() => {
                      goToReview(index);
                      setIsPaused(true);
                      setTimeout(() => setIsPaused(false), 3000);
                    }}
                    className={`h-2 sm:h-2.5 rounded-full transition-all duration-300 touch-manipulation ${
                      index === currentIndex
                        ? 'bg-blue-600 w-8 sm:w-10'
                        : 'bg-gray-300 hover:bg-gray-400 w-2 sm:w-2.5'
                    }`}
                    aria-label={`Go to slide ${index + 1}`}
                  />
                ))}
              </div>
            )}
          </div>

          {/* CTA to Leave Review */}
          <div className="mt-8 text-center">
            <p className="text-gray-600 mb-4 text-sm sm:text-base">
              {reviews.ctaText || "Share your experience with HResume"}
            </p>
            <Link
              to="/review"
              className="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
            >
              {reviews.ctaButton || "Leave a Review"}
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

