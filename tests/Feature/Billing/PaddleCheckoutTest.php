<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\ProSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaddleCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_tunisia_checkout_is_blocked(): void
    {
        config([
            'paddle.api_key' => 'test_api_key',
            'paddle.price_id' => 'pri_test',
        ]);

        $user = User::factory()->create(['is_pro' => false]);

        $response = $this->actingAs($user)->postJson('/api/billing/checkout', [
            'region' => 'tunisia',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'tunisia_checkout_unavailable');
    }

    public function test_international_checkout_returns_paddle_url(): void
    {
        config([
            'paddle.api_key' => 'test_api_key',
            'paddle.price_id' => 'pri_test',
            'paddle.api_base' => 'https://sandbox-api.paddle.com',
        ]);

        Http::fake([
            'sandbox-api.paddle.com/transactions' => Http::response([
                'data' => [
                    'id' => 'txn_test123',
                    'checkout' => ['url' => 'https://checkout.paddle.com/session/test'],
                ],
            ], 201),
        ]);

        $user = User::factory()->create(['is_pro' => false]);

        $response = $this->actingAs($user)->postJson('/api/billing/checkout', [
            'region' => 'international',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.gateway', 'paddle')
            ->assertJsonPath('data.url', 'https://checkout.paddle.com/session/test');
    }

    public function test_paddle_activate_and_deactivate_subscription(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $service = app(ProSubscriptionService::class);

        $service->activateFromPaddle($user, 'ctm_test', 'sub_test');

        $this->assertTrue($user->fresh()->is_pro);
        $this->assertSame('sub_test', $user->fresh()->paddle_subscription_id);

        $service->deactivateFromPaddleSubscription('sub_test');

        $this->assertFalse($user->fresh()->is_pro);
        $this->assertNull($user->fresh()->paddle_subscription_id);
    }
}
