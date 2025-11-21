<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Display a listing of public reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $reviews = Review::with('user:id,name,avatar')
            ->where('is_public', true)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Display the authenticated user's review.
     */
    public function myReview(Request $request): JsonResponse
    {
        $review = Review::where('user_id', $request->user()->id)->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $review,
        ]);
    }

    /**
     * Store a newly created review (or update if exists).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateReview($request);
        $user = $request->user();

        $review = Review::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, ['user_id' => $user->id])
        );

        return response()->json([
            'status' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        $this->authorizeReviewOwnership($review);

        $data = $this->validateReview($request);
        $review->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Review updated successfully.',
            'data' => $review,
        ]);
    }

    private function validateReview(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['required', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10'],
            'is_public' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeReviewOwnership(Review $review): void
    {
        if ($review->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to modify this review.');
        }
    }
}

