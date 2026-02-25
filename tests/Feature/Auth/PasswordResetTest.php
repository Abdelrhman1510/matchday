<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_password_reset_otp_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
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
    }

    /** @test */
    public function it_returns_422_for_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
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
    public function it_returns_422_for_invalid_email_format()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_email()
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['email'],
            ]);
    }

    /** @test */
    public function it_resets_password_with_valid_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('old_password'),
        ]);

        // Simulate OTP in cache
        $otp = '123456';
        Cache::put("password_reset_otp:{$user->email}", $otp, now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => $otp,
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
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

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('new_password123', $user->password));
    }

    /** @test */
    public function it_returns_422_for_invalid_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        Cache::put("password_reset_otp:{$user->email}", '123456', now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => '999999',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
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
    public function it_returns_422_for_expired_otp()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // OTP already expired (not in cache)
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => '123456',
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_returns_422_for_password_mismatch()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $otp = '123456';
        Cache::put("password_reset_otp:{$user->email}", $otp, now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => $otp,
            'password' => 'new_password123',
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
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $otp = '123456';
        Cache::put("password_reset_otp:{$user->email}", $otp, now()->addMinutes(15));

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => $otp,
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
    public function it_clears_otp_after_successful_reset()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $otp = '123456';
        Cache::put("password_reset_otp:{$user->email}", $otp, now()->addMinutes(15));

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'otp' => $otp,
            'password' => 'new_password123',
            'password_confirmation' => 'new_password123',
        ]);

        // Verify OTP is cleared
        $this->assertNull(Cache::get("password_reset_otp:{$user->email}"));
    }
}
