<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for the POST /api/v1/auth/verify-email endpoint.
 *
 * BUG-079 regression: an expired activation OTP must be rejected even when
 * a password-reset OTP (sharing the same email address) is active in cache.
 * Before the fix, both flows wrote to "otp:{email}", so a fresh pwd-reset
 * OTP would make the expired activation OTP appear valid.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeUnverifiedUser(): User
    {
        return User::factory()->create([
            'email'             => 'test@example.com',
            'email_verified_at' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    /** @test */
    public function it_verifies_email_with_valid_otp()
    {
        $user = $this->makeUnverifiedUser();
        $otp  = '123456';
        Cache::put("email_verify_otp:{$user->email}", $otp, now()->addMinutes(10));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => $otp]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    // -----------------------------------------------------------------------
    // Expiry & key-isolation tests (BUG-079)
    // -----------------------------------------------------------------------

    /** @test */
    public function it_rejects_otp_when_activation_code_has_expired()
    {
        // BUG-079: the cache key for the activation OTP has already expired
        // (i.e. no entry in cache). The endpoint must return 422, not 200.
        $user = $this->makeUnverifiedUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => '123456']);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.otp.0', 'Your OTP has expired. Please request a new one.');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * BUG-079 regression - key collision scenario:
     *
     * Before the fix, both sendEmailVerificationOtp() and sendPasswordResetOtp()
     * used "otp:{email}". If the activation OTP expired but the user then
     * requested a password-reset (refreshing the key with a new OTP), submitting
     * the pwd-reset OTP to /verify-email would succeed. The fix uses distinct
     * keys so the two flows can never interfere.
     *
     * @test
     */
    public function it_rejects_password_reset_otp_on_email_verification_endpoint()
    {
        $user        = $this->makeUnverifiedUser();
        $pwdResetOtp = '654321';

        // A live password-reset OTP is in cache under its dedicated key.
        // The verify-email endpoint must NOT accept it.
        Cache::put("pwd_reset_otp:{$user->email}", $pwdResetOtp, now()->addMinutes(10));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => $pwdResetOtp]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    // -----------------------------------------------------------------------
    // Invalid OTP
    // -----------------------------------------------------------------------

    /** @test */
    public function it_rejects_an_incorrect_otp()
    {
        $user = $this->makeUnverifiedUser();
        Cache::put("email_verify_otp:{$user->email}", '123456', now()->addMinutes(10));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => '999999']);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function it_returns_422_if_already_verified()
    {
        $user = User::factory()->create([
            'email'             => 'verified@example.com',
            'email_verified_at' => now(),
        ]);
        Cache::put("email_verify_otp:{$user->email}", '123456', now()->addMinutes(10));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => '123456']);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_clears_the_otp_after_successful_verification()
    {
        $user = $this->makeUnverifiedUser();
        $otp  = '123456';
        Cache::put("email_verify_otp:{$user->email}", $otp, now()->addMinutes(10));

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/verify-email', ['otp' => $otp]);

        $this->assertNull(Cache::get("email_verify_otp:{$user->email}"));
    }
}
