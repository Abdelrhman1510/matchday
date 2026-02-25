<?php

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_payment_method_successfully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payment-methods', [
            'type' => 'credit_card',
            'card_number' => '4111111111111111',
            'card_holder' => 'John Doe',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'type', 'last4', 'card_holder'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);
    }

    /** @test */
    public function it_lists_user_payment_methods()
    {
        $user = User::factory()->create();
        PaymentMethod::factory()->count(3)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'type', 'last4', 'is_primary'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_sets_payment_method_as_primary()
    {
        $user = User::factory()->create();
        $paymentMethod1 = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'is_primary' => true,
        ]);
        $paymentMethod2 = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'is_primary' => false,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod2->id}/set-primary");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $paymentMethod1->refresh();
        $paymentMethod2->refresh();
        
        $this->assertFalse($paymentMethod1->is_primary);
        $this->assertTrue($paymentMethod2->is_primary);
    }

    /** @test */
    public function it_updates_payment_method()
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/payment-methods/{$paymentMethod->id}", [
            'expiry_month' => '06',
            'expiry_year' => '2026',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_deletes_payment_method()
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /** @test */
    public function it_returns_403_for_other_user_payment_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user1->id]);
        
        Sanctum::actingAs($user2);

        $response = $this->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_card_number()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/payment-methods', [
            'type' => 'credit_card',
            'card_number' => '1234',
            'card_holder' => 'John Doe',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['card_number'],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_request()
    {
        $response = $this->getJson('/api/v1/payment-methods');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}
