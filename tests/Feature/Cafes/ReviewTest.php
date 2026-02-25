<?php

namespace Tests\Feature\Cafes;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_review_successfully()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great place to watch matches!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'rating', 'comment', 'user'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating' => 5,
                    'comment' => 'Great place to watch matches!',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'rating' => 5,
        ]);
    }

    /** @test */
    public function it_returns_422_for_duplicate_review()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($user);

        // Create first review
        Review::factory()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);

        // Try to create duplicate
        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'rating' => 4,
            'comment' => 'Another review',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_rating()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'rating' => 6,
            'comment' => 'Great place',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['rating'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_rating()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'comment' => 'Great place',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['rating'],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_review()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great place',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_lists_branch_reviews()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Review::factory()->count(3)->create(['branch_id' => $branch->id]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'rating', 'comment', 'user'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_allows_review_with_optional_comment()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/branches/{$branch->id}/reviews", [
            'rating' => 4,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating' => 4,
                ],
            ]);
    }
}
