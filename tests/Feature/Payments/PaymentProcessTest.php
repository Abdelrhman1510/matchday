<?php

namespace Tests\Feature\Payments;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentProcessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_payment_successfully()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'total_price' => 100,
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payment", [
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment_id',
                    'status',
                    'amount',
                    'transaction_id',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'amount' => 100,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_returns_422_for_already_paid_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);
        
        // Create existing payment
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payment", [
            'payment_method_id' => $paymentMethod->id,
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
    public function it_processes_refund_successfully()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'amount' => 100,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment_id',
                    'status',
                    'refund_amount',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded',
        ]);
    }

    /** @test */
    public function it_returns_422_for_refunding_non_completed_payment()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_403_for_other_user_payment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user1->id,
            'match_id' => $match->id,
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user1->id,
        ]);
        
        Sanctum::actingAs($user2);

        $response = $this->postJson("/api/v1/payments/{$payment->id}/refund");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_payment_method()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payment", []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['payment_method_id'],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_payment()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create(['match_id' => $match->id]);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payment", [
            'payment_method_id' => 1,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}
