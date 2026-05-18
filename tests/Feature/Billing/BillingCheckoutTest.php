<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_returns_503_when_stripe_not_configured(): void
    {
        config(['stripe.secret' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/billing/checkout');

        $response->assertStatus(503)
            ->assertJsonPath('code', 'billing_not_configured');
    }

    public function test_checkout_rejects_already_pro_user(): void
    {
        config([
            'stripe.secret' => 'sk_test_fake',
            'stripe.price_ids.international' => 'price_test',
        ]);

        $user = User::factory()->create(['is_pro' => true]);

        $response = $this->actingAs($user)->postJson('/api/billing/checkout');

        $response->assertStatus(422)
            ->assertJsonPath('code', 'already_pro');
    }
}
