<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsProDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_has_is_pro_false_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_pro);
        $this->assertFalse($user->fresh()->is_pro);
    }

    public function test_register_returns_is_pro_false(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'ispro-default@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => 'candidate',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'ispro-default@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_pro);
    }

    public function test_me_includes_is_pro_false_for_free_user(): void
    {
        $user = User::factory()->create(['is_pro' => false]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('user.is_pro', false);
    }
}
