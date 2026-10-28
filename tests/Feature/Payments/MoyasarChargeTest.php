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

    private function pendingBookingPayment(string $bookingStatus = 'pending'): Payment
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => $bookingStatus]);

        return Payment::create([
            'booking_id' => $booking->id, 'user_id' => $user->id,
            'amount' => 100, 'currency' => 'SAR', 'status' => 'pending', 'type' => 'booking',
        ]);
    }

    /** Moyasar's real record for pay_hook_1, as returned by GET /payments/{id}. */
    private function fakeRemote(string $status, int $amount = 10000): void
    {
        Http::fake([
            'api.moyasar.com/v1/payments/pay_hook_1' => Http::response(
                ['id' => 'pay_hook_1', 'status' => $status, 'amount' => $amount, 'currency' => 'SAR'], 200),
        ]);
    }

    private function hook(Payment $payment, string $claimedStatus)
    {
        return $this->postJson('/api/v1/webhooks/moyasar', [
            'secret_token' => 'whsec_test',
            'type' => "payment_{$claimedStatus}",
            'data' => ['id' => 'pay_hook_1', 'status' => $claimedStatus, 'metadata' => ['payment_id' => (string) $payment->id]],
        ]);
    }

    /** @test */
    public function webhook_marks_a_pending_payment_paid_and_confirms_the_booking()
    {
        $payment = $this->pendingBookingPayment();
        $this->fakeRemote('paid');

        $this->hook($payment, 'paid')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'completed', 'gateway_ref' => 'pay_hook_1']);
        $this->assertSame('confirmed', $payment->booking->fresh()->status);
    }

    /** @test */
    public function webhook_applies_moyasars_status_not_the_one_claimed_in_the_body()
    {
        $payment = $this->pendingBookingPayment();
        $this->fakeRemote('initiated'); // 3DS not completed on Moyasar's side

        $this->hook($payment, 'paid')->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $payment->booking->fresh()->status);
    }

    /** @test */
    public function webhook_ignores_a_moyasar_payment_whose_amount_differs_from_ours()
    {
        $payment = $this->pendingBookingPayment();
        $this->fakeRemote('paid', 100); // SAR 1, not our SAR 100

        $this->hook($payment, 'paid')->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    /** @test */
    public function duplicate_webhook_deliveries_are_applied_once()
    {
        $payment = $this->pendingBookingPayment();
        $this->fakeRemote('paid');

        $this->hook($payment, 'paid')->assertOk();
        $paidAt = $payment->fresh()->paid_at;
        $this->travel(5)->minutes();
        $this->hook($payment, 'paid')->assertOk();

        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertEquals($paidAt, $payment->fresh()->paid_at);
    }

    /** @test */
    public function a_late_paid_event_does_not_undo_a_refund()
    {
        $payment = $this->pendingBookingPayment();
        $payment->update(['status' => 'refunded', 'gateway_ref' => 'pay_hook_1']);
        $this->fakeRemote('paid'); // even if Moyasar were to report paid

        $this->hook($payment, 'paid')->assertOk();

        $this->assertSame('refunded', $payment->fresh()->status);
    }

    /** @test */
    public function webhook_is_retried_when_moyasar_cannot_be_reached()
    {
        $payment = $this->pendingBookingPayment();
        Http::fake(['api.moyasar.com/*' => Http::response([], 500)]);

        $this->hook($payment, 'paid')->assertStatus(503);

        $this->assertSame('pending', $payment->fresh()->status);
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
