<?php

namespace App\Http\Controllers;

use App\Services\PricingRegionService;
use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        private readonly StripeBillingService $billing,
        private readonly PricingRegionService $pricingRegion,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        if (! $this->billing->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Online checkout is not available yet. Contact support to upgrade.',
                'code' => 'billing_not_configured',
            ], 503);
        }

        $user = $request->user();

        if ($user->hasProAccess()) {
            return response()->json([
                'status' => false,
                'message' => 'You already have an active Pro subscription.',
                'code' => 'already_pro',
            ], 422);
        }

        try {
            $region = $request->input('region');
            if (! is_string($region) || ! in_array($region, ['tunisia', 'international'], true)) {
                $region = $this->pricingRegion->resolve($request)['region'];
            }
            $result = $this->billing->createCheckoutSession($user, $region);

            return response()->json([
                'status' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json([
                'status' => false,
                'message' => 'Could not start checkout. Please try again later.',
            ], 500);
        }
    }

    public function confirmSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:255',
        ]);

        if (! $this->billing->isConfigured()) {
            return response()->json([
                'status' => false,
                'message' => 'Billing is not configured.',
                'code' => 'billing_not_configured',
            ], 503);
        }

        try {
            $data = $this->billing->syncCheckoutSession(
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

    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->billing->handleWebhook(
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
}
