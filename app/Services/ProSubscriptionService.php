<?php

namespace App\Services;

use App\Models\User;

class ProSubscriptionService
{
    /**
     * Activate Pro from a paid Stripe subscription (requires verified email for effective access).
     */
    public function activateFromStripe(User $user, string $customerId, string $subscriptionId): void
    {
        $user->forceFill([
            'stripe_customer_id' => $customerId,
            'stripe_subscription_id' => $subscriptionId,
            'is_pro' => true,
        ])->save();
    }

    /**
     * Revoke Pro when Stripe subscription ends. Skips users without a matching subscription
     * (e.g. admin-granted Pro with no stripe_subscription_id).
     */
    public function deactivateFromStripeSubscription(string $subscriptionId): void
    {
        $user = User::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if (! $user) {
            return;
        }

        $user->forceFill([
            'is_pro' => false,
            'stripe_subscription_id' => null,
        ])->save();
    }

    public function findByStripeSubscription(string $subscriptionId): ?User
    {
        return User::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();
    }

    public function syncSubscriptionStatus(string $subscriptionId, string $status): void
    {
        $user = $this->findByStripeSubscription($subscriptionId);

        if (! $user) {
            return;
        }

        $active = in_array($status, ['active', 'trialing'], true);

        $user->forceFill(['is_pro' => $active])->save();
    }

    public function activateFromPaddle(User $user, string $customerId, string $subscriptionId): void
    {
        $user->forceFill([
            'paddle_customer_id' => $customerId,
            'paddle_subscription_id' => $subscriptionId,
            'is_pro' => true,
        ])->save();
    }

    public function deactivateFromPaddleSubscription(string $subscriptionId): void
    {
        $user = User::query()
            ->where('paddle_subscription_id', $subscriptionId)
            ->first();

        if (! $user) {
            return;
        }

        $user->forceFill([
            'is_pro' => false,
            'paddle_subscription_id' => null,
        ])->save();
    }

    public function findByPaddleSubscription(string $subscriptionId): ?User
    {
        return User::query()
            ->where('paddle_subscription_id', $subscriptionId)
            ->first();
    }

    public function syncPaddleSubscriptionStatus(string $subscriptionId, string $status): void
    {
        $user = $this->findByPaddleSubscription($subscriptionId);

        if (! $user) {
            return;
        }

        $active = in_array($status, ['active', 'trialing'], true);

        $user->forceFill(['is_pro' => $active])->save();
    }

    public function findByBillingUserId(mixed $userId): ?User
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        return User::find($userId);
    }
}
