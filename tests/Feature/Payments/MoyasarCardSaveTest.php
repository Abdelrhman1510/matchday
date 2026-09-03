<?php

namespace Tests\Feature\Payments;

use App\Jobs\ReleaseMoyasarHold;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MoyasarCardSaveTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token_abc123';
    private const PAYMENT_ID = 'pay_abc123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.moyasar.secret_key' => 'sk_test_x', 'services.moyasar.base_url' => 'https://api.moyasar.com/v1']);
    }

    /** Build the payment JSON Moyasar returns, with overridable fields. */
    private function paymentJson(array $overrides = []): array
    {
        return array_merge([
            'id' => self::PAYMENT_ID,
            'status' => 'authorized',
            'amount' => 100,
            'currency' => 'SAR',
            'metadata' => ['purpose' => 'card_verification'],
            'source' => ['type' => 'creditcard', 'token' => self::TOKEN],
        ], $overrides);
    }

    private function tokenJson(array $overrides = []): array
    {
        return array_merge([
            'id' => self::TOKEN,
            'brand' => 'visa',
            'last_four' => '1111',
            'month' => '12',
            'year' => '2028',
            'name' => 'FARES A',
            'funding' => 'credit',
            'country' => 'SA',
        ], $overrides);
    }

    private function fakeMoyasar(array $payment = null, array $token = null): void
    {
        Http::fake([
            'api.moyasar.com/v1/payments/*/void' => Http::response(['id' => self::PAYMENT_ID, 'status' => 'voided'], 200),
            'api.moyasar.com/v1/payments/*/refund' => Http::response(['id' => self::PAYMENT_ID, 'status' => 'refunded'], 200),
            'api.moyasar.com/v1/payments/*' => Http::response($payment ?? $this->paymentJson(), 200),
            'api.moyasar.com/v1/tokens/*' => Http::response($token ?? $this->tokenJson(), 200),
        ]);
    }

    private function submit(array $body = [])
    {
        return $this->postJson('/api/v1/payment-methods/moyasar', array_merge([
            'payment_id' => self::PAYMENT_ID,
            'token' => self::TOKEN,
            'is_primary' => false,
        ], $body));
    }

    /** @test */
    public function it_stores_a_verified_card_and_releases_the_hold()
    {
        Bus::fake();
        $this->fakeMoyasar();
        Sanctum::actingAs(User::factory()->create());

        $this->submit()
            ->assertStatus(201)
            ->assertJsonPath('data.card_brand', 'visa')
            ->assertJsonPath('data.card_last_four', '1111')
            ->assertJsonPath('data.card_holder', 'FARES A')
            ->assertJsonPath('data.expires_at', '2028-12')
            ->assertJsonPath('data.type', 'credit_card')
            ->assertJsonPath('data.is_primary', true); // first card

        $this->assertDatabaseHas('payment_methods', [
            'provider_payment_id' => self::PAYMENT_ID,
            'card_brand' => 'visa',
            'card_last_four' => '1111',
        ]);

        // Hold released via void (authorized), after the card is stored.
        Bus::assertDispatched(ReleaseMoyasarHold::class, fn ($job) => $job->paymentId === self::PAYMENT_ID && $job->mode === 'void');
    }

    /** @test */
    public function paid_authorization_is_refunded_not_voided()
    {
        Bus::fake();
        $this->fakeMoyasar($this->paymentJson(['status' => 'paid']));
        Sanctum::actingAs(User::factory()->create());

        $this->submit()->assertStatus(201);
        Bus::assertDispatched(ReleaseMoyasarHold::class, fn ($job) => $job->mode === 'refund');
    }

    /** @test */
    public function debit_funding_maps_to_debit_card()
    {
        Bus::fake();
        $this->fakeMoyasar(null, $this->tokenJson(['funding' => 'debit']));
        Sanctum::actingAs(User::factory()->create());

        $this->submit()->assertStatus(201)->assertJsonPath('data.type', 'debit_card');
    }

    /** @test */
    public function it_rejects_a_token_that_does_not_belong_to_the_payment()
    {
        Bus::fake();
        $this->fakeMoyasar($this->paymentJson(['source' => ['token' => 'someone_elses_token']]));
        Sanctum::actingAs(User::factory()->create());

        $this->submit()->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseCount('payment_methods', 0);
        Bus::assertNotDispatched(ReleaseMoyasarHold::class);
    }

    /** @test */
    public function it_rejects_a_wrong_amount()
    {
        $this->fakeMoyasar($this->paymentJson(['amount' => 5000]));
        Sanctum::actingAs(User::factory()->create());
        $this->submit()->assertStatus(422);
        $this->assertDatabaseCount('payment_methods', 0);
    }

    /** @test */
    public function it_rejects_a_wrong_purpose()
    {
        $this->fakeMoyasar($this->paymentJson(['metadata' => ['purpose' => 'booking']]));
        Sanctum::actingAs(User::factory()->create());
        $this->submit()->assertStatus(422);
        $this->assertDatabaseCount('payment_methods', 0);
    }

    /** @test */
    public function it_rejects_an_unauthorized_payment()
    {
        $this->fakeMoyasar($this->paymentJson(['status' => 'failed']));
        Sanctum::actingAs(User::factory()->create());
        $this->submit()->assertStatus(422);
        $this->assertDatabaseCount('payment_methods', 0);
    }

    /** @test */
    public function it_rejects_a_replayed_payment_id()
    {
        $this->fakeMoyasar();
        $user = User::factory()->create();
        PaymentMethod::factory()->create(['user_id' => $user->id, 'provider_payment_id' => self::PAYMENT_ID]);
        Sanctum::actingAs($user);

        $this->submit()->assertStatus(422)->assertJsonPath('message', 'This card has already been added.');
        $this->assertDatabaseCount('payment_methods', 1); // no new row
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->fakeMoyasar();
        $this->submit()->assertStatus(401);
    }
}
