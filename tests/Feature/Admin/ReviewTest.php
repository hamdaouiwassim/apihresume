<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have an admin role/middleware setup correctly for tests
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_list_all_reviews()
    {
        Review::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/reviews');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data');
    }

    public function test_admin_can_toggle_review_public_status()
    {
        $review = Review::factory()->create(['is_public' => true]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/reviews/{$review->id}/toggle-public");

        $response->assertStatus(200);
        $this->assertFalse($review->fresh()->is_public);
    }

    public function test_admin_can_delete_review()
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_non_admin_cannot_access_admin_review_routes()
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $review = Review::factory()->create();

        $this->actingAs($user)->getJson('/api/admin/reviews')->assertStatus(403);
        $this->actingAs($user)->patchJson("/api/admin/reviews/{$review->id}/toggle-public")->assertStatus(403);
        $this->actingAs($user)->deleteJson("/api/admin/reviews/{$review->id}")->assertStatus(403);
    }
}
