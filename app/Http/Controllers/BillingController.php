<?php

namespace App\Http\Controllers;

use App\Services\PaddleBillingService;
use App\Services\PricingRegionService;
use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        private readonly PaddleBillingService $paddle,
        private readonly StripeBillingService $stripe,
        private readonly PricingRegionService $pricingRegion,
    ) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'international_gateway' => config('billing.international_gateway', 'paddle'),
                'paddle_client_token' => config('paddle.client_token'),
                'paddle_configured' => $this->paddle->isConfigured(),
            ],
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasProAccess()) {
            return response()->json([
                'status' => false,
                'message' => 'You already have an active Pro subscription.',
                'code' => 'already_pro',
            ], 422);
        }

        $region = $request->input('region');
        if (! is_string($region) || ! in_array($region, ['tunisia', 'international'], true)) {
            $region = $this->pricingRegion->resolve($request)['region'];
        }

        if ($region === 'tunisia') {
            return response()->json([
                'status' => false,
                'message' => 'Online Pro checkout is available for international customers only. Tunisia: contact support to upgrade.',
                'code' => 'tunisia_checkout_unavailable',
            ], 422);
        }

        return $this->paddleCheckout($user);
    }

    public function confirmSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        if (! $request->input('session_id') && ! $request->input('transaction_id')) {
            return response()->json([
                'status' => false,
                'message' => 'session_id or transaction_id is required.',
            ], 422);
        }

        if ($request->filled('transaction_id')) {
            return $this->paddleConfirm($request);
        }

        return $this->stripeConfirm($request);
    }

    public function paddleWebhook(Request $request): JsonResponse
    {
        try {
            $this->paddle->handleWebhook(
                $request->getContent(),
                $request->header('Paddle-Signature')
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Throwable $e) {
            Log::error('Paddle webhook error', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook handler failed'], 500);
        }

        return response()->json(['received' => true]);
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        try {
            $this->stripe->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook handler failed'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function paddleCheckout($user): JsonResponse
    {
        if (! $this->paddle->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Online checkout is not available yet. Contact support to upgrade.',
                'code' => 'billing_not_configured',
            ], 503);
        }

        try {
            $result = $this->paddle->createCheckoutTransaction($user);

            return response()->json([
                'status' => true,
                'data' => [
                    'gateway' => 'paddle',
                    'transaction_id' => $result['transaction_id'],
                    'url' => $result['checkout_url'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Paddle checkout failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Could not start checkout. Please try again later.',
                'code' => 'paddle_error',
            ], 422);
        }
    }

    private function paddleConfirm(Request $request): JsonResponse
    {
        if (! $this->paddle->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Billing is not configured.',
                'code' => 'billing_not_configured',
            ], 503);
        }

        try {
            $data = $this->paddle->syncTransaction(
                $request->user(),
                $request->input('transaction_id')
            );

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Paddle transaction confirm failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not confirm your payment. If you were charged, contact support.',
            ], 422);
        }
    }

    private function stripeConfirm(Request $request): JsonResponse
    {
        if (! $this->stripe->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Billing is not configured.',
                'code' => 'billing_not_configured',
            ], 503);
        }

        try {
            $data = $this->stripe->syncCheckoutSession(
                $request->user(),
                $request->input('session_id')
            );

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe session confirm failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not confirm your payment. If you were charged, contact support.',
            ], 422);
        }
    }
}
