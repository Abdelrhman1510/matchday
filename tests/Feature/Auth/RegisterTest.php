<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\FanProfile;
use App\Models\LoyaltyCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_a_new_user_successfully()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+966512345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'locale' => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role'],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'fan',
        ]);
    }

    /** @test */
    public function it_auto_creates_fan_profile_on_registration()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+966523456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        
        $this->assertDatabaseHas('fan_profiles', [
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_auto_creates_loyalty_card_on_registration()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'phone' => '+966534567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        
        $user = User::where('email', 'bob@example.com')->first();
        
        $this->assertDatabaseHas('loyalty_cards', [
            'user_id' => $user->id,
            'points' => 0,
            'tier' => 'bronze',
        ]);
    }

    /** @test */
    public function it_returns_422_for_duplicate_email()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'phone' => '+966545678901',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_duplicate_phone()
    {
        User::factory()->create([
            'phone' => '+966556789012',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '+966556789012',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['phone'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_phone_format()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '123456',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['phone'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['name', 'email', 'password'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_password_mismatch()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+966567890123',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['password'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_short_password()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+966578901234',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['password'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_email_format()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'phone' => '+966589012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    /** @test */
    public function it_accepts_saudi_phone_format_with_zero()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Saudi User',
            'email' => 'saudi@example.com',
            'phone' => '0512345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'email' => 'saudi@example.com',
        ]);
    }
}
