<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\ProSubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_from_stripe_sets_pro_and_ids(): void
    {
        $user = User::factory()->create(['is_pro' => false]);
        $service = app(ProSubscriptionService::class);

        $service->activateFromStripe($user, 'cus_test', 'sub_test');

        $user->refresh();
        $this->assertTrue($user->is_pro);
        $this->assertSame('cus_test', $user->stripe_customer_id);
        $this->assertSame('sub_test', $user->stripe_subscription_id);
    }

    public function test_deactivate_only_affects_matching_subscription(): void
    {
        $paid = User::factory()->create([
            'is_pro' => true,
            'stripe_subscription_id' => 'sub_paid',
        ]);
        $adminGranted = User::factory()->create([
            'is_pro' => true,
            'stripe_subscription_id' => null,
        ]);

        app(ProSubscriptionService::class)->deactivateFromStripeSubscription('sub_paid');

        $this->assertFalse($paid->fresh()->is_pro);
        $this->assertNull($paid->fresh()->stripe_subscription_id);
        $this->assertTrue($adminGranted->fresh()->is_pro);
    }

    public function test_sync_subscription_status(): void
    {
        $user = User::factory()->create([
            'is_pro' => true,
            'stripe_subscription_id' => 'sub_123',
        ]);
        $service = app(ProSubscriptionService::class);

        $service->syncSubscriptionStatus('sub_123', 'past_due');
        $this->assertFalse($user->fresh()->is_pro);

        $service->syncSubscriptionStatus('sub_123', 'active');
        $this->assertTrue($user->fresh()->is_pro);
    }
}
