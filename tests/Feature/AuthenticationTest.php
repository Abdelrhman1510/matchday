<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\FanProfile;
use App\Models\LoyaltyCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration
     *
     * @return void
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
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
                        'fan_profile',
                        'loyalty_card',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'fan',
        ]);

        $this->assertDatabaseHas('fan_profiles', [
            'user_id' => User::where('email', 'john@example.com')->first()->id,
        ]);

        $this->assertDatabaseHas('loyalty_cards', [
            'user_id' => User::where('email', 'john@example.com')->first()->id,
            'tier' => 'bronze',
            'points' => 0,
        ]);
    }

    /**
     * Test cafe owner registration
     *
     * @return void
     */
    public function test_cafe_owner_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register/cafe-owner', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'cafe_owner',
        ]);

        // Cafe owners should not have fan profile or loyalty card
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertDatabaseMissing('fan_profiles', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test user login with email
     *
     * @return void
     */
    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'fan',
        ]);

        FanProfile::factory()->create(['user_id' => $user->id]);
        LoyaltyCard::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'fan_profile',
                        'loyalty_card',
                    ],
                    'token',
                ],
            ]);
    }

    /**
     * Test user login with phone
     *
     * @return void
     */
    public function test_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'password' => Hash::make('password123'),
            'role' => 'fan',
        ]);

        FanProfile::factory()->create(['user_id' => $user->id]);
        LoyaltyCard::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => '+1234567890',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                ],
            ]);
    }

    /**
     * Test login fails with invalid credentials
     *
     * @return void
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can logout
     *
     * @return void
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);
    }

    /**
     * Test user can refresh token
     *
     * @return void
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                ],
            ]);
    }

    /**
     * Test user can get their profile
     *
     * @return void
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create(['role' => 'fan']);
        FanProfile::factory()->create(['user_id' => $user->id]);
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'fan_profile',
                        'loyalty_card',
                    ],
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access protected routes
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/auth/refresh');
        $response->assertStatus(401);
    }

    /**
     * Test inactive user cannot login
     *
     * @return void
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'Your account has been deactivated.',
            ]);
    }
}
