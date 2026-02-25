<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_out_successfully_and_revokes_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Verify token is revoked - subsequent requests should fail
        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_logout()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
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
    public function it_returns_401_for_double_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // First logout
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Second logout should fail
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_revokes_only_current_token()
    {
        $user = User::factory()->create();
        
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        // Logout from device 1
        $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // Token 1 should be invalid
        $response1 = $this->withHeader('Authorization', "Bearer {$token1}")
            ->getJson('/api/v1/profile');
        $response1->assertStatus(401);

        // Token 2 should still be valid
        $response2 = $this->withHeader('Authorization', "Bearer {$token2}")
            ->getJson('/api/v1/profile');
        $response2->assertStatus(200);
    }

    /** @test */
    public function it_returns_valid_response_structure()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }
}
