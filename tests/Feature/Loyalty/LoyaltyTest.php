<?php

namespace Tests\Feature\Loyalty;

use App\Models\User;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_gets_user_loyalty_card()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 150,
            'tier' => 'silver',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/loyalty/card');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'points', 'tier', 'next_tier', 'progress'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'points' => 150,
                    'tier' => 'silver',
                ],
            ]);
    }

    /** @test */
    public function it_shows_tier_progress()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 250,
            'tier' => 'silver',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/loyalty/progress');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_tier',
                    'current_points',
                    'next_tier',
                    'points_needed',
                    'progress_percentage',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_awards_loyalty_points()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 100,
        ]);
        Sanctum::actingAs($user);

        $initialPoints = $loyaltyCard->points;

        // Simulate booking which awards points
        $response = $this->postJson('/api/v1/loyalty/award', [
            'points' => 50,
            'description' => 'Booking reward',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $loyaltyCard->refresh();
        $this->assertEquals($initialPoints + 50, $loyaltyCard->points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'loyalty_card_id' => $loyaltyCard->id,
            'points' => 50,
            'type' => 'earned',
        ]);
    }

    /** @test */
    public function it_upgrades_tier_when_threshold_reached()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 450,
            'tier' => 'silver',
        ]);
        Sanctum::actingAs($user);

        // Award points to reach gold tier (500 points)
        $this->postJson('/api/v1/loyalty/award', [
            'points' => 60,
            'description' => 'Tier upgrade test',
        ]);

        $loyaltyCard->refresh();
        $this->assertEquals('gold', $loyaltyCard->tier);
    }

    /** @test */
    public function it_lists_loyalty_transactions()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create(['user_id' => $user->id]);
        
        LoyaltyTransaction::factory()->count(5)->create([
            'loyalty_card_id' => $loyaltyCard->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/loyalty/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'points', 'type', 'description', 'created_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_redeems_loyalty_points()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 200,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/loyalty/redeem', [
            'points' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $loyaltyCard->refresh();
        $this->assertEquals(150, $loyaltyCard->points);

        $this->assertDatabaseHas('loyalty_transactions', [
            'loyalty_card_id' => $loyaltyCard->id,
            'points' => 50,
            'type' => 'redeemed',
        ]);
    }

    /** @test */
    public function it_returns_422_for_insufficient_points_redemption()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create([
            'user_id' => $user->id,
            'points' => 30,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/loyalty/redeem', [
            'points' => 50,
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
    public function it_returns_401_for_unauthenticated_loyalty_access()
    {
        $response = $this->getJson('/api/v1/loyalty/card');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_creates_loyalty_card_if_not_exists()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/loyalty/card');

        $response->assertStatus(200);

        $this->assertDatabaseHas('loyalty_cards', [
            'user_id' => $user->id,
        ]);
    }
}
