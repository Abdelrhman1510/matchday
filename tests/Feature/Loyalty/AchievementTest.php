<?php

namespace Tests\Feature\Loyalty;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_all_achievements()
    {
        Achievement::factory()->count(5)->create();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/achievements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'description', 'icon', 'requirement'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_lists_unlocked_achievements_for_user()
    {
        $user = User::factory()->create();
        
        $achievement1 = Achievement::factory()->create(['name' => 'First Booking']);
        $achievement2 = Achievement::factory()->create(['name' => 'Regular Fan']);
        Achievement::factory()->create(['name' => 'Not Unlocked']);
        
        UserAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement1->id,
        ]);
        UserAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement2->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/achievements/unlocked');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'description', 'unlocked_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_unlocks_achievement_for_user()
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create([
            'name' => 'First Match',
            'requirement' => 'Attend first match',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/achievements/{$achievement->id}/unlock");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['achievement', 'unlocked_at'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
    }

    /** @test */
    public function it_returns_422_for_already_unlocked_achievement()
    {
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create();
        
        UserAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/achievements/{$achievement->id}/unlock");

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
    public function it_shows_achievement_progress()
    {
        $user = User::factory()->create();
        
        $totalAchievements = Achievement::factory()->count(10)->create();
        
        $unlockedAchievements = Achievement::factory()->count(3)->create();
        foreach ($unlockedAchievements as $achievement) {
            UserAchievement::factory()->create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
            ]);
        }
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/achievements/progress');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_achievements',
                    'unlocked_count',
                    'progress_percentage',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_achievements' => 13,
                    'unlocked_count' => 3,
                ],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_achievement_access()
    {
        $response = $this->getJson('/api/v1/achievements');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_achievement()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/achievements/99999/unlock');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_lists_achievements_by_category()
    {
        Achievement::factory()->count(2)->create(['category' => 'bookings']);
        Achievement::factory()->count(3)->create(['category' => 'social']);
        
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/achievements?category=bookings');

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }
}
