<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaddleBillingService
{
    public function __construct(
        private readonly ProSubscriptionService $proSubscription,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('paddle.api_key'))
            && filled(config('paddle.price_id'));
    }

    /**
     * @return array{transaction_id: string, checkout_url: string|null}
     */
    public function createCheckoutTransaction(User $user): array
    {
        $this->ensureConfigured();

        $response = Http::withToken(config('paddle.api_key'))
            ->acceptJson()
            ->post(config('paddle.api_base').'/transactions', [
                'items' => [
                    [
                        'price_id' => config('paddle.price_id'),
                        'quantity' => 1,
                    ],
                ],
                'custom_data' => [
                    'user_id' => (string) $user->id,
                ],
                'collection_mode' => 'automatic',
            ]);

        if (! $response->successful()) {
            $message = $response->json('error.detail')
                ?? $response->json('error.message')
                ?? $response->body();

            throw new \RuntimeException('Paddle transaction failed: '.$message);
        }

        $data = $response->json('data') ?? [];
        $transactionId = (string) ($data['id'] ?? '');
        $checkoutUrl = $data['checkout']['url'] ?? null;

        if ($transactionId === '') {
            throw new \RuntimeException('Paddle did not return a transaction id.');
        }

        return [
            'transaction_id' => $transactionId,
            'checkout_url' => is_string($checkoutUrl) ? $checkoutUrl : null,
        ];
    }

    /**
     * After redirect, confirm transaction and activate Pro if paid.
     *
     * @return array{transaction_status: string, is_pro: bool}
     */
    public function syncTransaction(User $user, string $transactionId): array
    {
        $this->ensureConfigured();

        $response = Http::withToken(config('paddle.api_key'))
            ->acceptJson()
            ->get(config('paddle.api_base').'/transactions/'.$transactionId);

        if (! $response->successful()) {
            throw new \RuntimeException('Could not load Paddle transaction.');
        }

        $data = $response->json('data') ?? [];
        $customUserId = $data['custom_data']['user_id'] ?? null;

        if ((string) $customUserId !== (string) $user->id) {
            throw new \RuntimeException('Transaction does not belong to this user.');
        }

        $status = (string) ($data['status'] ?? '');
        $subscriptionId = (string) ($data['subscription_id'] ?? '');
        $customerId = (string) ($data['customer_id'] ?? '');

        if (in_array($status, ['completed', 'paid'], true) && $subscriptionId !== '') {
            $this->proSubscription->activateFromPaddle(
                $user,
                $customerId !== '' ? $customerId : ($user->paddle_customer_id ?? 'unknown'),
                $subscriptionId
            );
        }

        return [
            'transaction_status' => $status,
            'is_pro' => $user->fresh()->is_pro,
        ];
    }

    public function handleWebhook(string $rawBody, ?string $signatureHeader): void
    {
        $secret = config('paddle.webhook_secret');

        if (! filled($secret)) {
            Log::warning('Paddle webhook received but PADDLE_WEBHOOK_SECRET is not set.');

            return;
        }

        if (! $this->verifySignature($rawBody, $signatureHeader ?? '', $secret)) {
            throw new \UnexpectedValueException('Invalid Paddle webhook signature.');
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            throw new \UnexpectedValueException('Invalid Paddle webhook JSON.');
        }

        $eventType = (string) ($payload['event_type'] ?? '');
        $data = $payload['data'] ?? [];

        match ($eventType) {
            'subscription.activated', 'subscription.created' => $this->handleSubscriptionActive($data),
            'subscription.updated' => $this->handleSubscriptionUpdated($data),
            'subscription.canceled', 'subscription.past_due' => $this->handleSubscriptionEnded($data, $eventType),
            'transaction.completed' => $this->handleTransactionCompleted($data),
            default => null,
        };
    }

    private function handleSubscriptionActive(array $data): void
    {
        $user = $this->resolveUserFromPayload($data);

        if (! $user) {
            return;
        }

        $subscriptionId = (string) ($data['id'] ?? '');
        $customerId = (string) ($data['customer_id'] ?? '');

        if ($subscriptionId === '') {
            return;
        }

        $this->proSubscription->activateFromPaddle(
            $user,
            $customerId !== '' ? $customerId : ($user->paddle_customer_id ?? 'unknown'),
            $subscriptionId
        );
    }

    private function handleSubscriptionUpdated(array $data): void
    {
        $subscriptionId = (string) ($data['id'] ?? '');

        if ($subscriptionId === '') {
            return;
        }

        $this->proSubscription->syncPaddleSubscriptionStatus(
            $subscriptionId,
            (string) ($data['status'] ?? '')
        );
    }

    private function handleSubscriptionEnded(array $data, string $eventType): void
    {
        $subscriptionId = (string) ($data['id'] ?? '');

        if ($subscriptionId === '') {
            return;
        }

        if ($eventType === 'subscription.past_due') {
            $this->proSubscription->syncPaddleSubscriptionStatus($subscriptionId, 'past_due');

            return;
        }

        $this->proSubscription->deactivateFromPaddleSubscription($subscriptionId);
    }

    private function handleTransactionCompleted(array $data): void
    {
        $user = $this->resolveUserFromPayload($data);
        $subscriptionId = (string) ($data['subscription_id'] ?? '');

        if (! $user || $subscriptionId === '') {
            return;
        }

        $this->proSubscription->activateFromPaddle(
            $user,
            (string) ($data['customer_id'] ?? $user->paddle_customer_id ?? 'unknown'),
            $subscriptionId
        );
    }

    private function resolveUserFromPayload(array $data): ?User
    {
        $custom = $data['custom_data'] ?? [];

        if (is_array($custom) && isset($custom['user_id'])) {
            $user = $this->proSubscription->findByBillingUserId($custom['user_id']);

            if ($user) {
                return $user;
            }
        }

        $subscriptionId = (string) ($data['id'] ?? $data['subscription_id'] ?? '');

        if (str_starts_with($subscriptionId, 'sub_')) {
            return $this->proSubscription->findByPaddleSubscription($subscriptionId);
        }

        return null;
    }

    private function verifySignature(string $rawBody, string $signatureHeader, string $secret): bool
    {
        $parts = [];
        foreach (explode(';', $signatureHeader) as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $segment, 2);
            $parts[trim($key)] = trim($value);
        }

        if (empty($parts['ts']) || empty($parts['h1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['ts'].':'.$rawBody, $secret);

        return hash_equals($expected, $parts['h1']);
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Paddle billing is not configured.');
        }
    }
}
