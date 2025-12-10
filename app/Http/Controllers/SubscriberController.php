<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Mail\SubscriptionWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriberController extends Controller
{
    /**
     * Subscribe an email to the newsletter
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = strtolower(trim($request->email));

            // Check if email already exists
            $existingSubscriber = Subscriber::where('email', $email)->first();

            if ($existingSubscriber) {
                if ($existingSubscriber->is_active) {
                    return response()->json([
                        'status' => true,
                        'message' => 'You are already subscribed to our newsletter!',
                        'data' => [
                            'email' => $email,
                            'subscribed_at' => $existingSubscriber->subscribed_at,
                        ]
                    ], 200);
                } else {
                    // Reactivate subscription
                    $existingSubscriber->update([
                        'is_active' => true,
                        'subscribed_at' => now(),
                        'unsubscribed_at' => null,
                    ]);

                    // Send welcome email
                    try {
                        Mail::to($email)->queue(new SubscriptionWelcome($email));
                    } catch (\Exception $mailError) {
                        Log::warning('Failed to send subscription welcome email', [
                            'email' => $email,
                            'error' => $mailError->getMessage(),
                        ]);
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Welcome back! You have been resubscribed to our newsletter.',
                        'data' => [
                            'email' => $email,
                            'subscribed_at' => $existingSubscriber->subscribed_at,
                        ]
                    ], 200);
                }
            }

            // Create new subscriber
            $subscriber = Subscriber::create([
                'email' => $email,
                'is_active' => true,
                'subscribed_at' => now(),
            ]);

            // Send welcome email
            try {
                Mail::to($email)->queue(new SubscriptionWelcome($email));
            } catch (\Exception $mailError) {
                Log::warning('Failed to send subscription welcome email', [
                    'email' => $email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Successfully subscribed to our newsletter!',
                'data' => [
                    'email' => $subscriber->email,
                    'subscribed_at' => $subscriber->subscribed_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to subscribe email', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsubscribe an email from the newsletter
     */
    public function unsubscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = strtolower(trim($request->email));
            $subscriber = Subscriber::where('email', $email)->first();

            if (!$subscriber) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not found in our subscribers list.'
                ], 404);
            }

            if (!$subscriber->is_active) {
                return response()->json([
                    'status' => true,
                    'message' => 'You are already unsubscribed from our newsletter.'
                ], 200);
            }

            $subscriber->update([
                'is_active' => false,
                'unsubscribed_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'You have been successfully unsubscribed from our newsletter.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe email', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

