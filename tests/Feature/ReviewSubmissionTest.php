<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_submit_review()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/reviews', [
                'rating' => 5,
                'title' => 'Great App',
                'comment' => 'This is a really great app with many features.',
                'is_public' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true);
        
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    public function test_non_verified_user_cannot_submit_review()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/reviews', [
                'rating' => 5,
                'title' => 'Great App',
                'comment' => 'This is a really great app with many features.',
                'is_public' => true,
            ]);

        // Laravel's verified middleware usually redirects or returns 403
        $response->assertStatus(403);
    }
}
