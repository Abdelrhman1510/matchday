<?php

namespace Tests\Feature\Wallet;

use App\Models\BankAccount;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\CommissionOverride;
use App\Models\GameMatch;
use App\Models\LoyaltyCard;
use App\Models\MoyasarWebhookEvent;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Seat;
use App\Models\SeatingSection;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\Wallet\BankAccountService;
use App\Services\Wallet\BookingPaymentService;
use App\Services\Wallet\FeeSettingsService;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Contract §9: the acceptance tests for bookings, ledger, withdrawals and payouts. */
class CafeWalletFlowTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'SA0380000000608010167519';

    private User $owner;
    private Cafe $cafe;
    private Branch $branch;
    private GameMatch $match;
    private SeatingSection $section;
    private User $customer;
    private FeeSettingsService $fees;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config([
            'services.moyasar.secret_key' => 'sk_test_x',
            'services.moyasar.webhook_secret' => 'whsec_test',
            'services.moyasar.base_url' => 'https://api.moyasar.com/v1',
            'services.moyasar.payout_source_id' => 'src_1',
            'services.moyasar.payouts_enabled' => true,
        ]);

        $this->fees = app(FeeSettingsService::class);
        $this->owner = User::factory()->create(['role' => 'cafe_owner']);
        $this->cafe = Cafe::factory()->create(['owner_id' => $this->owner->id]);
        $plan = SubscriptionPlan::factory()->create(['max_bookings' => null, 'max_bookings_per_month' => null]);
        CafeSubscription::factory()->create([
            'cafe_id' => $this->cafe->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
        $this->branch = Branch::factory()->create(['cafe_id' => $this->cafe->id]);
        $this->section = SeatingSection::factory()->create(['branch_id' => $this->branch->id, 'extra_cost' => 0]);
        $this->match = GameMatch::factory()->create([
            'branch_id' => $this->branch->id, 'status' => 'upcoming', 'is_published' => true,
            'price_per_seat' => 45, 'seats_available' => 10,
            'match_date' => now()->addDays(2)->toDateString(), 'kick_off' => '20:00:00',
            'booking_opens_at' => now()->subDay(), 'booking_closes_at' => now()->addDay(),
        ]);
        $this->customer = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $this->customer->id]);
    }

    private function seats(int $count = 2): array
    {
        return Seat::factory()->count($count)->create(['section_id' => $this->section->id, 'is_available' => true])
            ->pluck('id')->all();
    }

    private function goOnline(array $extra = []): void
    {
        $this->fees->update(['payments' => ['online_enabled' => true]] + $extra, null);
    }

    private function book(?array $seatIds = null, ?User $as = null): array
    {
        Sanctum::actingAs($as ?? $this->customer);

        return $this->postJson('/api/v1/bookings', [
            'match_id' => $this->match->id,
            'seat_ids' => $seatIds ?? $this->seats(),
        ])->assertSuccessful()->json('data');
    }

    private function paidBooking(array $extra = []): Booking
    {
        $this->goOnline($extra);
        $data = $this->book();
        $booking = Booking::findOrFail($data['id']);
        $payment = $booking->payment;
        $payment->update(['status' => 'completed', 'gateway_ref' => 'pay_' . $booking->id, 'paid_at' => now()]);
        app(BookingPaymentService::class)->confirmPaid($booking, $payment);

        return $booking->fresh();
    }

    private function wallet(): Wallet
    {
        return app(WalletService::class)->walletFor($this->cafe);
    }

    private function balances(): array
    {
        return app(WalletService::class)->balances($this->wallet());
    }

    private function credit(float $amount): void
    {
        app(WalletService::class)->adjustment($this->wallet(), 'credit', $amount, 'test', $this->owner);
    }

    private function verifiedBank(): BankAccount
    {
        $bank = app(BankAccountService::class)->save($this->cafe, [
            'account_holder_name' => 'Goal Cafe', 'iban' => self::IBAN, 'city' => 'Riyadh', 'mobile' => '+966551234567',
        ]);
        app(BankAccountService::class)->verify($bank, $this->owner);

        return $bank->fresh();
    }

    private function withdrawal(float $amount = 500, string $key = 'k1'): WithdrawalRequest
    {
        return app(WithdrawalService::class)->request($this->cafe, $amount, $key, $this->owner);
    }

    // §9.1
    public function test_online_booking_is_pending_unpaid_with_seats_held_and_no_qr(): void
    {
        $this->goOnline();
        $seats = $this->seats();
        $data = $this->book($seats);

        $this->assertSame('pending', $data['status']);
        $this->assertSame('unpaid', $data['payment_status']);
        $this->assertNull($data['qr_code']);
        $this->assertNull($data['payment']);
        $this->assertEquals(95.0, $data['total_amount']);
        $this->assertNotNull($data['payment_expires_at']);
        $this->assertSame(8, $this->match->fresh()->seats_available);

        $other = User::factory()->create();
        Sanctum::actingAs($other);
        $this->postJson('/api/v1/bookings', ['match_id' => $this->match->id, 'seat_ids' => $seats])->assertStatus(422);
    }

    public function test_retrying_create_returns_the_same_pending_booking(): void
    {
        $this->goOnline();
        $seats = $this->seats();
        $first = $this->book($seats);

        Sanctum::actingAs($this->customer);
        $second = $this->postJson('/api/v1/bookings', ['match_id' => $this->match->id, 'seat_ids' => $seats])
            ->assertOk()->json('data');

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(1, Booking::count());
        $this->assertSame(8, $this->match->fresh()->seats_available);
    }

    // §9.2
    public function test_paying_confirms_and_replays_credit_only_once(): void
    {
        $booking = $this->paidBooking(['commission' => ['enabled' => true, 'type' => 'percent', 'value' => 5]]);

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->qr_code);
        $this->assertEquals(85.50, (float) $booking->cafe_net_amount);

        $service = app(BookingPaymentService::class);
        $this->assertFalse($service->confirmPaid($booking, $booking->payment));
        $this->assertFalse($service->confirmPaid($booking, $booking->payment));

        $this->assertSame(2, WalletTransaction::where('booking_id', $booking->id)->count());
        $this->assertEquals(85.50, $this->balances()['pending']);
        $this->assertEquals(0.0, $this->balances()['available']);
    }

    public function test_webhook_confirms_once_and_is_idempotent(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $payment = $booking->payment;
        $payment->update(['gateway_ref' => 'pay_wh']);
        Http::fake(['api.moyasar.com/v1/payments/pay_wh' => Http::response([
            'id' => 'pay_wh', 'status' => 'paid', 'amount' => 9500, 'currency' => 'SAR',
            'metadata' => ['payment_id' => (string) $payment->id],
        ])]);

        $body = [
            'id' => 'evt_1', 'type' => 'payment_paid', 'secret_token' => 'whsec_test', 'live' => true,
            'data' => ['id' => 'pay_wh', 'metadata' => ['payment_id' => (string) $payment->id]],
        ];
        $this->postJson('/api/v1/webhooks/payment/moyasar', $body)->assertOk();
        $this->postJson('/api/v1/webhooks/payment/moyasar', $body)->assertOk();

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, MoyasarWebhookEvent::count());
        $this->assertNotNull(MoyasarWebhookEvent::first()->processed_at);
        $this->assertArrayNotHasKey('secret_token', MoyasarWebhookEvent::first()->payload);
        $this->assertSame(1, WalletTransaction::where('booking_id', $booking->id)->where('type', 'booking_earning')->count());
    }

    public function test_webhook_rejects_a_bad_secret(): void
    {
        $this->postJson('/api/v1/webhooks/payment/moyasar', [
            'id' => 'evt_x', 'type' => 'payment_paid', 'secret_token' => 'nope', 'data' => [],
        ])->assertStatus(401);
        $this->assertSame(0, MoyasarWebhookEvent::count());
    }

    // §9.3
    public function test_unpaid_booking_expires_and_releases_seats(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);

        $this->travel(11)->minutes();
        $this->artisan('wallet:run expire-bookings')->assertSuccessful();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('payment_timeout', $booking->cancel_reason);
        $this->assertSame(10, $this->match->fresh()->seats_available);
    }

    public function test_late_payment_reconfirms_if_seats_are_free(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $this->travel(11)->minutes();
        app(BookingPaymentService::class)->expireUnpaid();

        $payment = $booking->payment;
        $payment->update(['status' => 'completed', 'gateway_ref' => 'pay_late']);
        app(BookingPaymentService::class)->confirmPaid($booking->fresh(), $payment);

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(8, $this->match->fresh()->seats_available);
    }

    public function test_late_payment_is_refunded_if_seats_were_taken(): void
    {
        $this->goOnline();
        $seats = $this->seats();
        $booking = Booking::findOrFail($this->book($seats)['id']);
        $this->travel(11)->minutes();
        app(BookingPaymentService::class)->expireUnpaid();
        $this->book($seats, User::factory()->create());

        Http::fake(['api.moyasar.com/v1/payments/pay_late/refund' => Http::response(['id' => 'pay_late', 'status' => 'refunded'])]);
        config(['payment.gateway' => 'moyasar']);
        $this->app->bind(\App\Contracts\PaymentGatewayInterface::class, \App\Services\Payment\MoyasarGateway::class);

        $payment = $booking->payment;
        $payment->update(['status' => 'completed', 'gateway_ref' => 'pay_late']);
        app(BookingPaymentService::class)->confirmPaid($booking->fresh(), $payment);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('booking_id', $booking->id)->count());
    }

    // §9.4
    public function test_pay_at_cafe_has_qr_and_no_wallet_entry_after_check_in(): void
    {
        $data = $this->book();
        $this->assertSame('pending', $data['status']);
        $this->assertSame('pay_at_cafe', $data['payment_method']);
        $this->assertNotNull($data['qr_code']);
        $this->assertNull(Booking::find($data['id'])->payment_expires_at);

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/cafe-admin/bookings/{$data['id']}/check-in")->assertSuccessful();

        $booking = Booking::find($data['id']);
        $this->assertSame('checked_in', $booking->status);
        $this->assertSame('paid_at_cafe', $booking->payment_status);
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_check_in_rejects_an_unpaid_online_booking(): void
    {
        $this->goOnline();
        $data = $this->book();

        Sanctum::actingAs($this->owner);
        $this->withHeader('Accept-Language', 'en')
            ->postJson("/api/v1/cafe-admin/bookings/{$data['id']}/check-in")
            ->assertStatus(422)->assertJsonPath('message', 'Booking not paid');
        $this->postJson('/api/v1/cafe-admin/scan-qr', ['qr_data' => $data['booking_code']])->assertStatus(422);

        $this->assertSame('pending', Booking::find($data['id'])->status);
    }

    // §9.5, §9.6
    public function test_fee_settings_drive_public_config_and_new_bookings_only(): void
    {
        $this->goOnline();
        $old = $this->book();

        Sanctum::actingAs(User::factory()->create(['role' => 'cafe_owner']));
        $this->fees->update(['booking_fee' => ['type' => 'percent', 'value' => 10, 'min' => 2, 'max' => 20]], null);
        $this->getJson('/api/v1/config/fees')->assertOk()
            ->assertJsonPath('data.booking_fee.type', 'percent')
            ->assertJsonPath('data.online_payments_enabled', true);

        $new = $this->book($this->seats(), User::factory()->create());
        $this->assertEquals(9.0, $new['service_fee']);
        $this->assertEquals(5.0, (float) Booking::find($old['id'])->service_fee);
    }

    public function test_disabled_fee_and_commission_count_as_zero(): void
    {
        $booking = $this->paidBooking(['booking_fee' => ['enabled' => false], 'commission' => ['enabled' => false, 'value' => 10]]);

        $this->assertEquals(0.0, (float) $booking->service_fee);
        $this->assertEquals((float) $booking->subtotal, (float) $booking->cafe_net_amount);
    }

    public function test_fee_settings_validation(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->fees->update(['withdrawals' => ['min_amount' => 5, 'fee' => 5]], null);
    }

    // §9.7
    public function test_commission_precedence_cafe_over_plan_over_default(): void
    {
        $this->fees->update(['commission' => ['enabled' => true, 'type' => 'percent', 'value' => 10]], null);
        $planId = $this->cafe->subscriptions()->value('plan_id');

        $this->assertEquals(10.0, $this->fees->commission(100, 1, $this->fees->commissionRule($this->cafe)));

        CommissionOverride::create(['scope' => 'plan', 'scope_id' => $planId, 'type' => 'percent', 'value' => 7]);
        $this->assertEquals(7.0, $this->fees->commission(100, 1, $this->fees->commissionRule($this->cafe)));

        CommissionOverride::create(['scope' => 'cafe', 'scope_id' => $this->cafe->id, 'type' => 'fixed_per_booking', 'value' => 3]);
        $this->assertEquals(3.0, $this->fees->commission(100, 1, $this->fees->commissionRule($this->cafe)));
    }

    // §9.8
    public function test_check_in_releases_the_earning_immediately(): void
    {
        $booking = $this->paidBooking();
        $this->assertEquals(90.0, $this->balances()['pending']);

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/cafe-admin/bookings/{$booking->id}/check-in")->assertSuccessful();

        $this->assertEquals(90.0, $this->balances()['available']);
        $this->assertEquals(0.0, $this->balances()['pending']);
    }

    public function test_no_show_releases_at_kick_off(): void
    {
        $this->paidBooking();

        $this->travelTo(now()->addDays(2)->setTime(16, 59));
        $this->artisan('wallet:run promote-earnings');
        $this->assertEquals(0.0, $this->balances()['available']);

        $this->travelTo(now()->setTime(17, 1)); // 20:00 Riyadh is 17:00 UTC
        $this->artisan('wallet:run promote-earnings');
        $this->assertEquals(90.0, $this->balances()['available']);
    }

    public function test_postponed_match_moves_the_release(): void
    {
        $booking = $this->paidBooking();

        $this->match->fresh()->update(['match_date' => now()->addDays(5)->toDateString()]);

        $row = WalletTransaction::where('booking_id', $booking->id)->first();
        $this->assertTrue($row->available_at->gt(now()->addDays(4)));
    }

    public function test_refund_before_release_reverses_and_after_release_debits(): void
    {
        $booking = $this->paidBooking();
        app(WalletService::class)->reverseBooking($booking);
        $this->assertEquals(0.0, $this->balances()['total']);
        $this->assertSame('reversed', WalletTransaction::where('booking_id', $booking->id)->value('status'));

        $this->match->update(['seats_available' => 10]);
        $other = User::factory()->create();
        $second = Booking::findOrFail($this->book($this->seats(), $other)['id']);
        $second->payment->update(['status' => 'completed', 'gateway_ref' => 'pay_2']);
        app(BookingPaymentService::class)->confirmPaid($second, $second->payment);
        app(BookingPaymentService::class)->checkIn($second->fresh());
        $this->assertEquals(90.0, $this->balances()['available']);

        app(WalletService::class)->reverseBooking($second->fresh());
        app(WalletService::class)->reverseBooking($second->fresh());
        $this->assertEquals(0.0, $this->balances()['available']);
        $this->assertSame(1, WalletTransaction::where('booking_id', $second->id)->where('type', 'refund')->count());
    }

    // §9.9
    public function test_withdrawal_rule_errors_are_localized(): void
    {
        Sanctum::actingAs($this->owner);
        $this->withHeader('Accept-Language', 'ar');
        $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'a'])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'bank_account_missing')
            ->assertJsonPath('message', 'أضف حسابًا بنكيًا لسحب أرباحك.');

        $bank = $this->verifiedBank();
        $bank->update(['status' => 'pending_verification']);
        $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'b'])
            ->assertStatus(422)->assertJsonPath('errors.code.0', 'bank_account_unverified');

        $bank->update(['status' => 'verified']);
        $this->credit(200);
        $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 50, 'idempotency_key' => 'c'])
            ->assertStatus(422)->assertJsonPath('errors.code.0', 'amount_below_min');
        $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'd'])
            ->assertStatus(422)->assertJsonPath('errors.code.0', 'insufficient_balance');
    }

    public function test_preview_writes_nothing(): void
    {
        $this->verifiedBank();
        $this->credit(600);
        $this->fees->update(['withdrawals' => ['fee' => 2.5]], null);

        Sanctum::actingAs($this->owner);
        $this->postJson('/api/v1/cafe-admin/wallet/withdrawals/preview', ['amount' => 500])->assertOk()
            ->assertJsonPath('data.net_amount', 497.5);
        $this->assertSame(0, WithdrawalRequest::count());
    }

    // §9.10
    public function test_same_idempotency_key_creates_one_request(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        Sanctum::actingAs($this->owner);

        $a = $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'same'])->assertCreated();
        $b = $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'same'])->assertOk();

        $this->assertSame($a->json('data.id'), $b->json('data.id'));
        $this->assertSame(1, WithdrawalRequest::count());
        $this->assertEquals(500.0, $this->balances()['available']);
        $this->assertEquals(500.0, $this->balances()['pending_withdrawal']);
    }

    // §9.11
    public function test_owner_cancel_restores_balance_once_and_not_after_approval(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        Sanctum::actingAs($this->owner);
        $id = $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'x'])->json('data.id');

        $this->postJson("/api/v1/cafe-admin/wallet/withdrawals/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->postJson("/api/v1/cafe-admin/wallet/withdrawals/{$id}/cancel")->assertStatus(422);
        $this->assertEquals(1000.0, $this->balances()['available']);

        Http::fake(['api.moyasar.com/v1/payouts' => Http::response(['id' => 'p', 'status' => 'queued'], 201)]);
        $w = $this->withdrawal(500, 'y');
        app(WithdrawalService::class)->approveAndSend($w, $this->owner);
        $this->postJson("/api/v1/cafe-admin/wallet/withdrawals/{$w->id}/cancel")->assertStatus(422);
    }

    // §9.12
    public function test_approve_sends_payout_and_polling_marks_it_paid(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();

        Http::fake(function ($request) use ($w) {
            if ($request->method() === 'POST') {
                $body = $request->data();
                $this->assertSame(50000, $body['amount']);
                $this->assertSame('payment_to_merchant', $body['purpose']);
                $this->assertSame('0551234567', $body['destination']['mobile']);
                $this->assertSame((string) $w->id, $body['metadata']['withdrawal_id']);

                return Http::response(['id' => $body['given_id'], 'status' => 'initiated', 'channel' => 'internal', 'sequence_number' => '1'], 201);
            }

            return Http::response(['id' => $w->fresh()->payout_uuid, 'status' => 'paid']);
        });

        app(WithdrawalService::class)->approveAndSend($w, $this->owner);
        $this->assertSame('processing', $w->fresh()->status);
        $this->assertSame('internal', $w->fresh()->moyasar_channel);

        $this->artisan('wallet:run poll-payouts')->assertSuccessful();
        $this->assertSame('paid', $w->fresh()->status);
        $this->assertNotNull($w->fresh()->paid_at);
        $this->assertEquals(500.0, $this->balances()['total_withdrawn']);
        $this->assertEquals(500.0, $this->balances()['available']);
    }

    public function test_network_error_resends_the_same_given_id(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        $ids = [];

        Http::fake(function ($request) use (&$ids) {
            $ids[] = $request->data()['given_id'];

            return count($ids) === 1
                ? Http::response(['message' => 'boom'], 503)
                : Http::response(['id' => $ids[0], 'status' => 'queued'], 201);
        });

        app(WithdrawalService::class)->approveAndSend($w, $this->owner);
        $this->assertSame('approved', $w->fresh()->status);

        $this->travel(3)->minutes();
        Http::preventStrayRequests(false);
        app(WithdrawalService::class)->sendPayout($w->id);

        $this->assertSame('processing', $w->fresh()->status);
        $this->assertCount(2, array_unique($ids) === $ids ? [] : [1, 2]);
        $this->assertSame($ids[0], $ids[1]);
    }

    public function test_used_given_id_blocks_without_failing_or_resending(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        Http::fake(['api.moyasar.com/v1/payouts' => Http::response(['type' => 'used_given_id', 'message' => 'used'], 400)]);

        app(WithdrawalService::class)->approveAndSend($w, $this->owner);

        $this->assertSame('approved', $w->fresh()->status);
        $this->assertTrue($w->fresh()->payout_blocked);
        $this->assertEquals(500.0, $this->balances()['available']);
    }

    // §9.13
    public function test_failed_and_returned_payouts_restore_balance_once(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        Http::fake(['api.moyasar.com/v1/payouts' => Http::response(['id' => 'po', 'status' => 'initiated'], 201)]);
        $service = app(WithdrawalService::class);
        $service->approveAndSend($w, $this->owner);

        $service->applyPayout($w->fresh(), ['id' => 'po', 'status' => 'paid']);
        $this->assertSame('paid', $w->fresh()->status);

        $service->applyPayout($w->fresh(), ['id' => 'po', 'status' => 'returned', 'failure_reason' => 'bank']);
        $service->applyPayout($w->fresh(), ['id' => 'po', 'status' => 'returned']);

        $this->assertSame('failed', $w->fresh()->status);
        $this->assertSame(1, WalletTransaction::where('withdrawal_id', $w->id)->where('type', 'withdrawal_reversal')->count());
        $this->assertEquals(1000.0, $this->balances()['available']);
    }

    public function test_retry_re_debits_and_uses_a_new_given_id(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        $service = app(WithdrawalService::class);

        Http::fakeSequence('api.moyasar.com/v1/payouts')
            ->push(['id' => 'po1', 'status' => 'initiated'], 201)
            ->push(['id' => 'po2', 'status' => 'initiated'], 201);
        Http::fake(['api.moyasar.com/v1/payouts/po1' => Http::response(['id' => 'po1', 'status' => 'failed'])]);

        $service->approveAndSend($w, $this->owner);
        $firstUuid = $w->fresh()->payout_uuid;
        $service->applyPayout($w->fresh(), ['id' => 'po1', 'status' => 'failed', 'message' => 'declined']);
        $this->assertEquals(1000.0, $this->balances()['available']);

        $service->retry($w->fresh(), $this->owner);

        $this->assertNotSame($firstUuid, $w->fresh()->payout_uuid);
        $this->assertSame('processing', $w->fresh()->status);
        $this->assertEquals(500.0, $this->balances()['available']);
    }

    public function test_cannot_reject_while_a_payout_is_live(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        $w->update(['status' => 'approved', 'payout_uuid' => (string) \Illuminate\Support\Str::uuid()]);
        Http::fake(['api.moyasar.com/v1/payouts/*' => Http::response(['id' => 'x', 'status' => 'initiated'])]);

        $this->expectException(\App\Exceptions\WalletException::class);
        app(WithdrawalService::class)->reject($w->fresh(), $this->owner, 'no');
    }

    // §9.14
    public function test_reject_shows_reason_and_restores_balance(): void
    {
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();

        app(WithdrawalService::class)->reject($w, $this->owner, 'Name mismatch');

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/cafe-admin/wallet/withdrawals')->assertOk()
            ->assertJsonPath('data.0.status', 'rejected')
            ->assertJsonPath('data.0.rejection_reason', 'Name mismatch');
        $this->assertEquals(1000.0, $this->balances()['available']);
    }

    // §9.15
    public function test_manual_mark_paid_needs_no_moyasar_call(): void
    {
        Http::fake();
        $this->fees->update(['withdrawals' => ['payout_mode' => 'manual']], null);
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();

        app(WithdrawalService::class)->markPaidManually($w, $this->owner, 'TRX-1', now()->toDateString());

        $this->assertSame('paid', $w->fresh()->status);
        Http::assertNothingSent();
    }

    // §9.16
    public function test_staff_get_403_on_every_wallet_and_bank_endpoint(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        \App\Models\StaffMember::forceCreate([
            'user_id' => $staff->id, 'cafe_id' => $this->cafe->id,
            'role' => 'manager', 'invitation_status' => 'accepted',
        ]);
        \Illuminate\Support\Facades\DB::table('branch_staff')->insert(['user_id' => $staff->id, 'branch_id' => $this->branch->id]);
        Sanctum::actingAs($staff);

        foreach ([
            ['GET', 'wallet'], ['GET', 'wallet/transactions'], ['GET', 'wallet/withdrawals'],
            ['POST', 'wallet/withdrawals/preview'], ['POST', 'wallet/withdrawals'], ['POST', 'wallet/withdrawals/1/cancel'],
            ['GET', 'bank-account'], ['PUT', 'bank-account'], ['DELETE', 'bank-account'],
        ] as [$method, $path]) {
            $this->json($method, "/api/v1/cafe-admin/{$path}")->assertStatus(403);
        }
    }

    // §9.17
    public function test_full_iban_never_appears_in_any_app_response(): void
    {
        Sanctum::actingAs($this->owner);
        $bank = $this->putJson('/api/v1/cafe-admin/bank-account', [
            'account_holder_name' => 'Goal Cafe', 'iban' => 'sa03 8000 0000 6080 1016 7519', 'city' => 'Riyadh', 'mobile' => '0551234567',
        ])->assertOk();
        $this->assertSame('pending_verification', $bank->json('data.status'));
        $this->assertSame('Al Rajhi Bank', $bank->json('data.bank_name'));
        $this->assertStringNotContainsString(self::IBAN, $bank->getContent());
        $this->assertNotSame(self::IBAN, \Illuminate\Support\Facades\DB::table('bank_accounts')->value('iban'));

        app(BankAccountService::class)->verify(BankAccount::first(), $this->owner);
        $this->credit(1000);
        $withdrawal = $this->postJson('/api/v1/cafe-admin/wallet/withdrawals', ['amount' => 500, 'idempotency_key' => 'z']);

        foreach ([$withdrawal, $this->getJson('/api/v1/cafe-admin/bank-account'), $this->getJson('/api/v1/cafe-admin/wallet/withdrawals')] as $response) {
            $this->assertStringNotContainsString(self::IBAN, $response->getContent());
        }
    }

    public function test_iban_checksum_is_enforced_and_a_change_needs_reverification(): void
    {
        Sanctum::actingAs($this->owner);
        $this->putJson('/api/v1/cafe-admin/bank-account', [
            'account_holder_name' => 'X', 'iban' => 'SA0380000000608010167518', 'city' => 'Riyadh', 'mobile' => '0551234567',
        ])->assertStatus(422);

        $this->verifiedBank();
        $this->putJson('/api/v1/cafe-admin/bank-account', [
            'account_holder_name' => 'New Name', 'iban' => self::IBAN, 'city' => 'Jeddah', 'mobile' => '0551234567',
        ])->assertOk()->assertJsonPath('data.status', 'pending_verification');
    }

    public function test_only_platform_admin_can_use_the_fees_admin_api(): void
    {
        Sanctum::actingAs($this->owner);
        $this->getJson('/api/v1/admin/settings/fees')->assertStatus(403);

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('platform_admin', 'web'));
        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/admin/settings/fees', ['booking_fee' => ['value' => 7]])->assertOk()->assertJsonPath('data.booking_fee.value', 7);
        $this->getJson('/api/v1/admin/settings/fees/history')->assertOk()->assertJsonPath('data.0.key', 'booking_fee');
    }

    public function test_subscriptions_off_returns_one_free_plan(): void
    {
        $this->fees->update(['subscriptions' => ['enabled' => false]], null);

        Sanctum::actingAs($this->owner);
        $response = $this->getJson('/api/v1/subscription/plans')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(0, $response->json('data.0.price'));
    }

    public function test_dashboard_pages_render_for_platform_admin(): void
    {
        $admin = User::factory()->create();
        $role = Role::findOrCreate('platform_admin', 'web');
        $role->givePermissionTo(['withdrawals.view', 'withdrawals.approve', 'finance.reveal_iban']);
        $admin->assignRole($role);
        $this->verifiedBank();
        $this->credit(1000);
        $this->withdrawal();

        $this->actingAs($admin);
        $this->get('/platform/fees')->assertOk()->assertSee('45.00');
        $this->get('/platform/withdrawals')->assertOk();
        $this->get('/platform/bank-accounts')->assertOk();
    }

    public function test_default_booking_fee_matches_the_contract(): void
    {
        $this->assertSame(
            ['enabled' => true, 'type' => 'fixed', 'value' => 5.0, 'min' => 0.0, 'max' => 0.0],
            $this->fees->block('booking_fee')
        );
        $this->assertEquals(5.0, $this->fees->serviceFee(45, 1));
        $this->assertEquals(5.0, $this->fees->serviceFee(400, 4));
    }

    public function test_no_payout_can_be_sent_while_payouts_are_disabled(): void
    {
        Http::fake();
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();
        config(['services.moyasar.payouts_enabled' => false]);

        try {
            app(WithdrawalService::class)->approveAndSend($w, $this->owner);
            $this->fail('Approve must be refused while payouts are disabled.');
        } catch (\App\Exceptions\WalletException $e) {
            $this->assertSame('payout_not_configured', $e->errorCode);
        }
        $this->assertSame('pending', $w->fresh()->status);

        // Even a request already approved must not leave through the jobs or the client.
        $w->update(['status' => 'approved', 'payout_uuid' => (string) \Illuminate\Support\Str::uuid(), 'updated_at' => now()->subHour()]);
        app(WithdrawalService::class)->pollOpenPayouts();
        app(WithdrawalService::class)->sendPayout($w->id);
        Http::assertNothingSent();

        $this->expectException(\RuntimeException::class);
        app(\App\Services\Payment\MoyasarClient::class)->createPayout(['amount' => 100]);
    }

    public function test_tick_never_runs_subscription_renewals(): void
    {
        $source = file_get_contents(app_path('Console/Commands/WalletJobsCommand.php'))
            . file_get_contents(base_path('docker-entrypoint.sh'));

        $this->assertStringNotContainsString('schedule:', $source);
        $this->assertStringNotContainsString('subscriptions:', $source);
        \Illuminate\Support\Facades\Artisan::call('wallet:run', ['task' => 'tick']);
        $this->assertStringNotContainsStringIgnoringCase('renew', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_tick_runs_wallet_jobs_and_daily_jobs_only_once(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $this->travelTo(now('Asia/Riyadh')->setTime(10, 3)->addDay()->utc());

        $this->artisan('wallet:run tick')->assertSuccessful();
        $this->assertSame('cancelled', $booking->fresh()->status);
        $stamp = \App\Models\PlatformSetting::where('key', 'wallet_tick.notify-earnings')->value('value');
        $this->assertSame(now('Asia/Riyadh')->toDateString(), $stamp);

        // A new earning after today's digest must wait for tomorrow's.
        $this->credit(10);
        WalletTransaction::where('type', 'adjustment')->update(['type' => 'booking_earning']);
        $this->artisan('wallet:run tick')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_a_dashboard_plan_can_be_assigned_to_a_cafe(): void
    {
        $this->cafe->update(['subscription_plan' => 'custom-plan-ab12']);
        $this->assertSame('custom-plan-ab12', $this->cafe->fresh()->subscription_plan);

        $this->cafe->update(['subscription_plan' => null]);
        $this->assertNull($this->cafe->fresh()->subscription_plan);
    }

    private function fakeMoyasarPayment(string $id, int $amount, array &$refunds, string &$status): void
    {
        Http::fake(function ($request) use ($id, $amount, &$refunds, &$status) {
            if (str_ends_with($request->url(), "/payments/{$id}/refund")) {
                $refunds[] = $id;
                $status = 'refunded';

                return Http::response(['id' => $id, 'status' => 'refunded']);
            }

            return Http::response(['id' => $id, 'status' => $status, 'amount' => $amount, 'currency' => 'SAR']);
        });
    }

    public function test_reconcile_webhook_and_charge_confirm_a_payment_only_once(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $payment = $booking->payment;
        $payment->update(['gateway_ref' => 'pay_once', 'created_at' => now()->subMinutes(6)]);
        $refunds = [];
        $status = 'paid';
        $this->fakeMoyasarPayment('pay_once', 9500, $refunds, $status);

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->postJson('/api/v1/webhooks/payment/moyasar', [
            'id' => 'evt_once', 'type' => 'payment_paid', 'secret_token' => 'whsec_test',
            'data' => ['id' => 'pay_once'],
        ])->assertOk();
        app(BookingPaymentService::class)->confirmPaid($booking->fresh(), $payment->fresh());

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame(1, WalletTransaction::where('booking_id', $booking->id)->where('type', 'booking_earning')->count());
        // One flat reward plus one amount-based award, and no more after the replays.
        $earned = \App\Models\LoyaltyTransaction::where('booking_id', $booking->id)->where('type', 'earned');
        $this->assertSame(2, (clone $earned)->count());
        $this->assertSame(1, (clone $earned)->where('description', 'Booking reward')->count());
        $this->assertSame([], $refunds);
    }

    public function test_reconcile_settles_a_late_payment_whose_seats_were_taken_with_one_refund(): void
    {
        $this->goOnline();
        $seats = $this->seats();
        $booking = Booking::findOrFail($this->book($seats)['id']);
        $payment = $booking->payment;
        $payment->update(['gateway_ref' => 'pay_taken']);
        $this->travel(11)->minutes();
        app(BookingPaymentService::class)->expireUnpaid();
        $this->book($seats, User::factory()->create());

        $refunds = [];
        $status = 'paid';
        $this->fakeMoyasarPayment('pay_taken', 9500, $refunds, $status);
        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(['pay_taken'], $refunds);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(0, WalletTransaction::where('booking_id', $booking->id)->count());
    }

    public function test_reconcile_retries_a_failed_refund_but_never_touches_older_bookings(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $booking->update(['status' => 'cancelled', 'cancel_reason' => 'payment_timeout']);
        $booking->payment->update(['status' => 'completed', 'gateway_ref' => 'pay_retry']);

        $old = Booking::factory()->create(['status' => 'cancelled', 'match_id' => $this->match->id, 'branch_id' => $this->branch->id]);
        $oldPayment = Payment::factory()->create(['booking_id' => $old->id, 'status' => 'completed', 'gateway_ref' => 'pay_old', 'type' => 'booking']);

        $refunds = [];
        $status = 'paid';
        $this->fakeMoyasarPayment('pay_retry', 9500, $refunds, $status);
        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(['pay_retry'], $refunds);
        $this->assertSame('completed', $oldPayment->fresh()->status);
    }

    public function test_refund_already_made_on_moyasar_is_not_sent_again(): void
    {
        $this->goOnline();
        $booking = Booking::findOrFail($this->book()['id']);
        $booking->update(['status' => 'cancelled', 'cancel_reason' => 'refunded']);
        $booking->payment->update(['status' => 'completed', 'gateway_ref' => 'pay_done']);
        $refunds = [];
        $status = 'refunded';
        $this->fakeMoyasarPayment('pay_done', 9500, $refunds, $status);

        \App\Jobs\RefundBookingPayment::dispatchSync($booking->payment->id);

        $this->assertSame([], $refunds);
        $this->assertSame('refunded', $booking->payment->fresh()->status);
    }

    public function test_pay_at_cafe_earns_points_only_at_check_in_and_once(): void
    {
        $data = $this->book();
        $earned = \App\Models\LoyaltyTransaction::where('booking_id', $data['id'])->where('type', 'earned');
        $this->assertSame(0, (clone $earned)->count());

        Sanctum::actingAs($this->owner);
        $this->postJson("/api/v1/cafe-admin/bookings/{$data['id']}/check-in")->assertSuccessful();
        $this->postJson("/api/v1/cafe-admin/bookings/{$data['id']}/check-in")->assertStatus(422);

        $this->assertSame(2, (clone $earned)->count());
    }

    public function test_tick_runs_payments_reconcile_once_per_interval(): void
    {
        $this->artisan('wallet:run tick')->expectsOutputToContain('pending payment(s)')->assertSuccessful();
        $this->artisan('wallet:run tick')->doesntExpectOutputToContain('pending payment(s)')->assertSuccessful();

        $this->travel(10)->minutes();
        $this->artisan('wallet:run tick')->expectsOutputToContain('pending payment(s)')->assertSuccessful();
    }

    public function test_daily_jobs_follow_riyadh_time(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2030-01-10 01:59:00', 'Asia/Riyadh')->utc());
        $this->artisan('wallet:run tick')->assertSuccessful();
        $this->assertNull(\App\Models\PlatformSetting::where('key', 'wallet_tick.reconcile')->value('value'));

        $this->travelTo(\Carbon\Carbon::parse('2030-01-10 02:00:00', 'Asia/Riyadh')->utc());
        $this->artisan('wallet:run tick')->assertSuccessful();
        $this->assertSame('2030-01-10', \App\Models\PlatformSetting::where('key', 'wallet_tick.reconcile')->value('value'));
        $this->assertNull(\App\Models\PlatformSetting::where('key', 'wallet_tick.notify-earnings')->value('value'));

        $this->travelTo(\Carbon\Carbon::parse('2030-01-10 09:00:00', 'Asia/Riyadh')->utc());
        $this->artisan('wallet:run tick')->assertSuccessful();
        $this->assertSame('2030-01-10', \App\Models\PlatformSetting::where('key', 'wallet_tick.notify-earnings')->value('value'));
    }

    public function test_production_webhook_mode_must_match_the_key(): void
    {
        $event = fn (string $id, $live) => [
            'id' => $id, 'type' => 'payment_failed', 'secret_token' => 'whsec_test', 'live' => $live, 'data' => ['id' => 'x'],
        ];
        $this->app['env'] = 'production';

        config(['services.moyasar.secret_key' => 'sk_test_x']);
        $this->postJson('/api/v1/webhooks/payment/moyasar', $event('t1', false))->assertOk();
        $this->postJson('/api/v1/webhooks/payment/moyasar', $event('t2', true))->assertStatus(403);

        config(['services.moyasar.secret_key' => 'sk_live_x']);
        $this->postJson('/api/v1/webhooks/payment/moyasar', $event('l1', true))->assertOk();
        $this->postJson('/api/v1/webhooks/payment/moyasar', $event('l2', false))->assertStatus(403);

        $this->postJson('/api/v1/webhooks/payment/moyasar', [
            'id' => 'v1', 'type' => 'webhook_verification', 'secret_token' => 'whsec_test', 'data' => ['verification_code' => '123456'],
        ])->assertOk();
        $this->assertSame(3, MoyasarWebhookEvent::count());
        $this->app['env'] = 'testing';
    }

    public function test_iban_reveal_is_logged(): void
    {
        $admin = User::factory()->create();
        $role = Role::findOrCreate('platform_admin', 'web');
        $role->givePermissionTo(['withdrawals.view', 'withdrawals.approve', 'finance.reveal_iban']);
        $admin->assignRole($role);
        $this->verifiedBank();
        $this->credit(1000);
        $w = $this->withdrawal();

        $this->actingAs($admin);
        \Livewire\Livewire::test(\App\Livewire\Platform\WithdrawalsPage::class)
            ->call('select', $w->id)
            ->call('revealIban')
            ->assertSet('revealedIban', self::IBAN);

        $this->assertDatabaseHas('withdrawal_events', ['withdrawal_id' => $w->id, 'action' => 'iban_revealed', 'user_id' => $admin->id]);
    }
}
