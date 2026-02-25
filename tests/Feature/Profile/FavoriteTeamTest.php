<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\Team;
use App\Models\FanProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sets_favorite_team_successfully()
    {
        $user = User::factory()->create();
        $fanProfile = FanProfile::factory()->create(['user_id' => $user->id]);
        $team = Team::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/favorite-team', [
            'team_id' => $team->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('fan_profiles', [
            'user_id' => $user->id,
            'favorite_team_id' => $team->id,
        ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_team_id()
    {
        $user = User::factory()->create();
        FanProfile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/favorite-team', [
            'team_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['team_id'],
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_updates_existing_favorite_team()
    {
        $user = User::factory()->create();
        $oldTeam = Team::factory()->create();
        $newTeam = Team::factory()->create();
        $fanProfile = FanProfile::factory()->create([
            'user_id' => $user->id,
            'favorite_team_id' => $oldTeam->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/favorite-team', [
            'team_id' => $newTeam->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('fan_profiles', [
            'user_id' => $user->id,
            'favorite_team_id' => $newTeam->id,
        ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_request()
    {
        $team = Team::factory()->create();

        $response = $this->putJson('/api/v1/profile/favorite-team', [
            'team_id' => $team->id,
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_team_id()
    {
        $user = User::factory()->create();
        FanProfile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/favorite-team', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['team_id'],
            ]);
    }

    /** @test */
    public function it_removes_favorite_team_with_null()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $fanProfile = FanProfile::factory()->create([
            'user_id' => $user->id,
            'favorite_team_id' => $team->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile/favorite-team', [
            'team_id' => null,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('fan_profiles', [
            'user_id' => $user->id,
            'favorite_team_id' => null,
        ]);
    }
}
