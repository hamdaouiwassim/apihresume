<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of all reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search');

        $query = Review::with('user:id,name,email,avatar');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('title', 'like', "%{$search}%")
              ->orWhere('comment', 'like', "%{$search}%");
        }

        $reviews = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Toggle the public visibility of a review.
     */
    public function togglePublic(Review $review): JsonResponse
    {
        $review->is_public = !$review->is_public;
        $review->save();

        return response()->json([
            'status' => true,
            'message' => 'Review visibility toggled successfully.',
            'data' => $review,
        ]);
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }
}
