<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MoyasarChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.moyasar.secret_key' => 'sk_test_x',
            'services.moyasar.webhook_secret' => 'whsec_test',
            'services.moyasar.base_url' => 'https://api.moyasar.com/v1',
        ]);
    }

    /** @test */
    public function upgrading_a_plan_charges_the_stored_token()
    {
        Http::fake([
            'api.moyasar.com/v1/payments' => Http::response(['id' => 'pay_charge_1', 'status' => 'paid'], 201),
        ]);

        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        $pro = SubscriptionPlan::factory()->create(['name' => 'Pro', 'price' => 59.99]);
        $card = PaymentMethod::factory()->create(['user_id' => $owner->id, 'provider_token' => 'token_live_1']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/upgrade", [
            'plan_id' => $pro->id,
            'payment_method_id' => $card->id,
        ])->assertStatus(200)->assertJsonPath('success', true);

        // Charged the real token for the server-side plan price (59.99 -> 5999 halalas).
        Http::assertSent(fn ($req) => $req->url() === 'https://api.moyasar.com/v1/payments'
            && $req['amount'] === 5999
            && $req['source']['token'] === 'token_live_1');

        $this->assertDatabaseHas('payments', ['type' => 'subscription', 'status' => 'paid', 'gateway_ref' => 'pay_charge_1']);
    }

    /** @test */
    public function a_declined_upgrade_does_not_change_the_subscription()
    {
        Http::fake([
            'api.moyasar.com/v1/payments' => Http::response(['id' => 'pay_declined', 'status' => 'failed', 'source' => ['message' => 'Card declined.']], 201),
        ]);

        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $basic = SubscriptionPlan::factory()->create(['price' => 29.99]);
        $pro = SubscriptionPlan::factory()->create(['price' => 59.99]);
        $sub = CafeSubscription::factory()->create(['cafe_id' => $cafe->id, 'plan_id' => $basic->id, 'status' => 'active', 'expires_at' => now()->addMonth()]);
        $card = PaymentMethod::factory()->create(['user_id' => $owner->id, 'provider_token' => 'token_bad']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/upgrade", [
            'plan_id' => $pro->id,
            'payment_method_id' => $card->id,
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertSame($basic->id, $sub->fresh()->plan_id); // unchanged
        $this->assertDatabaseHas('payments', ['type' => 'subscription', 'status' => 'failed']);
    }

    /** @test */
    public function webhook_marks_a_pending_payment_paid_and_confirms_the_booking()
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        $payment = Payment::create([
            'booking_id' => $booking->id, 'user_id' => $user->id,
            'amount' => 100, 'currency' => 'SAR', 'status' => 'pending', 'type' => 'booking',
        ]);

        $this->postJson('/api/v1/webhooks/moyasar', [
            'secret_token' => 'whsec_test',
            'type' => 'payment_paid',
            'data' => ['id' => 'pay_hook_1', 'status' => 'paid', 'metadata' => ['payment_id' => (string) $payment->id]],
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'gateway_ref' => 'pay_hook_1']);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    /** @test */
    public function webhook_rejects_a_bad_secret()
    {
        $this->postJson('/api/v1/webhooks/moyasar', [
            'secret_token' => 'wrong',
            'data' => ['id' => 'x', 'status' => 'paid'],
        ])->assertStatus(401);
    }
}
