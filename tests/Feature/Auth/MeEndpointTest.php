<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\FanProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_includes_needs_team_selection_true_when_no_team()
    {
        $user = User::factory()->create();
        FanProfile::create([
            'user_id' => $user->id,
            'favorite_team_id' => null,
            'member_since' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'needs_team_selection' => true,
                        'onboarding_complete' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_includes_needs_team_selection_false_when_team_set()
    {
        $user = User::factory()->create();
        $team = \App\Models\Team::factory()->create();
        FanProfile::create([
            'user_id' => $user->id,
            'favorite_team_id' => $team->id,
            'member_since' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'needs_team_selection' => false,
                        'onboarding_complete' => true,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_includes_needs_team_selection_true_when_no_fan_profile()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'needs_team_selection' => true,
                        'onboarding_complete' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_user_profile_data_in_me()
    {
        $user = User::factory()->create([
            'name' => 'Ahmed Al-Fahd',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role',
                        'needs_team_selection',
                        'onboarding_complete',
                        'fan_profile',
                        'loyalty_card',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'Ahmed Al-Fahd',
                        'email' => 'ahmed@example.com',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_requires_auth_for_me()
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
