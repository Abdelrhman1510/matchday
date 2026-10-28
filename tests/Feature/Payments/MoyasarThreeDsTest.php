<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionRenewalNeedsConfirmationNotification;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Saved-card charges where the issuer answers "initiated" with a 3DS transaction_url. */
class MoyasarThreeDsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://api.moyasar.com/v1/card_auth/abc/prepare';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.moyasar.secret_key' => 'sk_test_x',
            'services.moyasar.webhook_secret' => 'whsec_test',
            'services.moyasar.base_url' => 'https://api.moyasar.com/v1',
        ]);
    }

    /** What Moyasar's GET /payments/pay_3ds currently reports; tests flip it mid-flow. */
    private string $remoteStatus = 'initiated';
    private bool $faked = false;

    /** POST /payments answers 3DS-required; GET /payments/pay_3ds reports $remoteStatus. */
    private function fakeMoyasar(string $remoteStatus = 'initiated'): void
    {
        $this->remoteStatus = $remoteStatus;
        if ($this->faked) {
            return; // a second Http::fake() would sit behind the first, so reuse it
        }
        $this->faked = true;

        Http::fake(function (Request $req) {
            $initiated = ['type' => 'creditcard', 'transaction_url' => self::URL];
            if ($req->method() === 'POST' && $req->url() === 'https://api.moyasar.com/v1/payments') {
                return Http::response(['id' => 'pay_3ds', 'status' => 'initiated', 'source' => $initiated], 201);
            }
            if ($req->method() === 'GET' && $req->url() === 'https://api.moyasar.com/v1/payments/pay_3ds') {
                $p = Payment::where('gateway_ref', 'pay_3ds')->first();
                return Http::response(['id' => 'pay_3ds', 'status' => $this->remoteStatus,
                    'amount' => (int) round(($p->amount ?? 0) * 100), 'currency' => 'SAR',
                    'metadata' => ['payment_id' => (string) $p?->id],
                    'source' => $this->remoteStatus === 'initiated' ? $initiated : ['type' => 'creditcard']], 200);
            }
            return Http::response([], 404);
        });
    }

    private function chargesSent(): int
    {
        return Http::recorded(fn ($req) => $req->method() === 'POST' && $req->url() === 'https://api.moyasar.com/v1/payments')->count();
    }

    private function unpaidBooking(): array
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed', 'total_amount' => 70]);
        Payment::create(['booking_id' => $booking->id, 'user_id' => $user->id, 'amount' => 70,
            'currency' => 'SAR', 'status' => 'pending', 'type' => 'booking']);
        $card = PaymentMethod::factory()->create(['user_id' => $user->id, 'provider_token' => 'token_1']);
        Sanctum::actingAs($user);

        return [$booking, $card];
    }

    /** @test */
    public function payments_process_returns_the_transaction_url_and_keeps_the_charge_pending()
    {
        $this->fakeMoyasar();
        [$booking, $card] = $this->unpaidBooking();

        $res = $this->postJson('/api/v1/payments/process', ['booking_id' => $booking->id, 'payment_method_id' => $card->id]);

        $payment = Payment::where('booking_id', $booking->id)->first();
        $res->assertStatus(400)->assertExactJson([
            'success' => false,
            'message' => 'Your bank needs to verify this payment. Complete the verification to finish.',
            'data' => ['requires_action' => true, 'transaction_url' => self::URL, 'payment_id' => $payment->id],
        ]);
        // Committed despite the error response, so the webhook can find it by Moyasar id.
        $this->assertSame('pending', $payment->status);
        $this->assertSame('pay_3ds', $payment->gateway_ref);
    }

    /** @test */
    public function retrying_while_3ds_is_open_resumes_it_without_charging_again()
    {
        $this->fakeMoyasar('initiated');
        [$booking, $card] = $this->unpaidBooking();
        $body = ['booking_id' => $booking->id, 'payment_method_id' => $card->id];

        $this->postJson('/api/v1/payments/process', $body)->assertStatus(400);
        $this->postJson('/api/v1/payments/process', $body)
            ->assertStatus(400)->assertJsonPath('data.transaction_url', self::URL);

        $this->assertSame(1, $this->chargesSent());
    }

    /** @test */
    public function retrying_after_3ds_completed_returns_success_without_charging_again()
    {
        $this->fakeMoyasar('initiated');
        [$booking, $card] = $this->unpaidBooking();
        $body = ['booking_id' => $booking->id, 'payment_method_id' => $card->id];
        $this->postJson('/api/v1/payments/process', $body)->assertStatus(400);

        $this->fakeMoyasar('paid'); // customer finished 3DS; webhook not here yet
        $this->postJson('/api/v1/payments/process', $body)->assertOk()->assertJsonPath('success', true);

        $this->assertSame('completed', Payment::where('booking_id', $booking->id)->value('status'));
        $this->assertSame(1, $this->chargesSent()); // the original, nothing new
    }

    /** @test */
    public function a_declined_booking_charge_is_recorded_as_failed()
    {
        Http::fake(['api.moyasar.com/v1/payments' => Http::response(
            ['id' => 'pay_no', 'status' => 'failed', 'source' => ['message' => 'Insufficient funds']], 201)]);
        [$booking, $card] = $this->unpaidBooking();

        $this->postJson('/api/v1/payments/process', ['booking_id' => $booking->id, 'payment_method_id' => $card->id])
            ->assertStatus(400)->assertJsonPath('errors.message', 'Insufficient funds');

        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id, 'status' => 'failed', 'gateway_ref' => 'pay_no']);
    }

    private function ownerWithPlans(): array
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $basic = SubscriptionPlan::factory()->create(['price' => 29.99]);
        $pro = SubscriptionPlan::factory()->create(['price' => 70, 'slug' => 'pro-3ds']);
        $sub = CafeSubscription::factory()->create(['cafe_id' => $cafe->id, 'plan_id' => $basic->id,
            'status' => 'active', 'expires_at' => now()->addMonth()]);
        $card = PaymentMethod::factory()->create(['user_id' => $owner->id, 'provider_token' => 'token_owner']);
        Sanctum::actingAs($owner);

        return [$cafe, $pro, $sub, $card];
    }

    /** @test */
    public function upgrade_returns_the_transaction_url_and_the_webhook_applies_the_plan_after_3ds()
    {
        $this->fakeMoyasar('initiated');
        [$cafe, $pro, $sub, $card] = $this->ownerWithPlans();

        $res = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/upgrade",
            ['plan_id' => $pro->id, 'payment_method_id' => $card->id]);

        $payment = Payment::where('type', 'subscription')->latest('id')->first();
        $res->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.requires_action', true)
            ->assertJsonPath('data.transaction_url', self::URL)
            ->assertJsonPath('data.payment_id', $payment->id);
        $this->assertNotSame($pro->id, $sub->fresh()->plan_id); // not upgraded yet

        // Customer completes 3DS; Moyasar posts the webhook.
        $this->fakeMoyasar('paid');
        $this->postJson('/api/v1/webhooks/moyasar', ['secret_token' => 'whsec_test', 'type' => 'payment_paid',
            'data' => ['id' => 'pay_3ds', 'status' => 'paid']])->assertOk();

        $this->assertSame($pro->id, $sub->fresh()->plan_id);
        $this->assertSame('pro-3ds', $cafe->fresh()->subscription_plan);
        $this->assertSame($sub->id, $payment->fresh()->subscription_id);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    /** @test */
    public function a_3ds_renewal_is_flagged_past_due_and_the_owner_is_notified_then_reactivated_by_webhook()
    {
        Notification::fake();
        $this->fakeMoyasar('initiated');
        [$cafe, , $sub, $card] = $this->ownerWithPlans();
        $sub->update(['auto_renew' => true, 'payment_method_id' => $card->id, 'expires_at' => now()->addHours(12)]);
        $sub->plan->update(['price' => 70]);
        $expires = $sub->fresh()->expires_at;

        $this->assertFalse(app(SubscriptionService::class)->processRenewal($sub->fresh()));

        $this->assertSame('past_due', $sub->fresh()->status);
        Notification::assertSentTo($cafe->owner, SubscriptionRenewalNeedsConfirmationNotification::class);

        $this->fakeMoyasar('paid');
        $this->postJson('/api/v1/webhooks/moyasar', ['secret_token' => 'whsec_test', 'type' => 'payment_paid',
            'data' => ['id' => 'pay_3ds', 'status' => 'paid']])->assertOk();

        $this->assertSame('active', $sub->fresh()->status);
        $this->assertTrue($sub->fresh()->expires_at->equalTo($expires->copy()->addMonth()));
    }
}
