<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeBillingService
{
    public function __construct(
        private readonly ProSubscriptionService $proSubscription,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('stripe.secret'))
            && filled(config('stripe.price_ids.international'));
    }

    public function priceIdForRegion(string $region): ?string
    {
        $key = $region === 'tunisia' ? 'tunisia' : 'international';
        $priceId = config("stripe.price_ids.{$key}");

        return filled($priceId) ? $priceId : null;
    }

    /**
     * @return array{url: string, session_id: string}
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(User $user, string $region = 'international'): array
    {
        $this->ensureConfigured();

        $priceId = $this->priceIdForRegion($region);

        if (! $priceId) {
            throw new \RuntimeException('Stripe price is not configured for region: '.$region);
        }

        Stripe::setApiKey(config('stripe.secret'));

        $customerId = $user->stripe_customer_id;

        if (! $customerId) {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => (string) $user->id],
            ]);
            $customerId = $customer->id;
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $session = Session::create([
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
            'success_url' => config('stripe.checkout.success_url').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('stripe.checkout.cancel_url'),
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ],
        ]);

        return [
            'url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    /**
     * Confirm checkout after redirect and sync Pro if payment succeeded.
     */
    public function syncCheckoutSession(User $user, string $sessionId): array
    {
        $this->ensureConfigured();

        Stripe::setApiKey(config('stripe.secret'));

        $session = Session::retrieve([
            'id' => $sessionId,
            'expand' => ['subscription'],
        ]);

        if ((string) ($session->client_reference_id ?? '') !== (string) $user->id
            && (string) ($session->metadata['user_id'] ?? '') !== (string) $user->id) {
            throw new \RuntimeException('Checkout session does not belong to this user.');
        }

        $status = $session->status ?? '';
        $paymentStatus = $session->payment_status ?? '';

        if ($status === 'complete' && $paymentStatus === 'paid' && filled($session->subscription)) {
            $subscriptionId = is_string($session->subscription)
                ? $session->subscription
                : $session->subscription->id;

            $this->proSubscription->activateFromStripe(
                $user,
                (string) $session->customer,
                $subscriptionId
            );
        }

        return [
            'session_status' => $status,
            'payment_status' => $paymentStatus,
            'is_pro' => $user->fresh()->is_pro,
        ];
    }

    public function handleWebhook(string $payload, ?string $signatureHeader): void
    {
        $secret = config('stripe.webhook_secret');

        if (! filled($secret)) {
            Log::warning('Stripe webhook received but STRIPE_WEBHOOK_SECRET is not set.');

            return;
        }

        $event = Webhook::constructEvent(
            $payload,
            $signatureHeader ?? '',
            $secret
        );

        $type = $event->type;
        $object = $event->data->object;

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            default => null,
        };
    }

    private function handleCheckoutCompleted(object $session): void
    {
        if (($session->mode ?? '') !== 'subscription') {
            return;
        }

        $userId = $session->metadata->user_id ?? $session->client_reference_id ?? null;
        $subscriptionId = $session->subscription ?? null;

        if (! $userId || ! $subscriptionId) {
            return;
        }

        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $this->proSubscription->activateFromStripe(
            $user,
            (string) $session->customer,
            (string) $subscriptionId
        );
    }

    private function handleSubscriptionUpdated(object $subscription): void
    {
        $subscriptionId = (string) ($subscription->id ?? '');

        if ($subscriptionId === '') {
            return;
        }

        $this->proSubscription->syncSubscriptionStatus(
            $subscriptionId,
            (string) ($subscription->status ?? '')
        );
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $subscriptionId = (string) ($subscription->id ?? '');

        if ($subscriptionId !== '') {
            $this->proSubscription->deactivateFromStripeSubscription($subscriptionId);
        }
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe billing is not configured.');
        }
    }
}
