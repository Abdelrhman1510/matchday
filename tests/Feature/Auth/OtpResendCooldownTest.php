<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OtpResendCooldownTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function forgot_password_otp_enforces_a_resend_cooldown()
    {
        Notification::fake();
        User::factory()->create(['email' => 'user@example.com']);

        // First request is accepted and sends the OTP.
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'user@example.com'])
            ->assertStatus(200);

        // An immediate second request is blocked by the per-identifier cooldown.
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'user@example.com'])
            ->assertStatus(429);
    }
}
