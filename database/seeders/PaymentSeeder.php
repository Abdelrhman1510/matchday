<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $fans = User::where('role', 'fan')->get();

        // Add payment methods for fans
        foreach ($fans->take(3) as $fan) {
            // Credit Card
            $cardType = fake()->randomElement(['credit_card', 'debit_card']);
            PaymentMethod::create([
                'user_id' => $fan->id,
                'type' => $cardType,
                'card_last_four' => rand(1000, 9999),
                'card_holder' => $fan->name,
                'expires_at' => sprintf('%02d/%02d', rand(1, 12), rand(26, 30)),
                'is_primary' => true,
                'provider_token' => 'tok_' . strtoupper(\Illuminate\Support\Str::random(24)),
            ]);

            // Digital Wallet (for some users)
            if (rand(0, 1)) {
                PaymentMethod::create([
                    'user_id' => $fan->id,
                    'type' => 'wallet',
                    'is_primary' => false,
                    'provider_token' => 'wallet_' . strtoupper(\Illuminate\Support\Str::random(24)),
                ]);
            }
        }

        // Create payments for bookings
        $bookings = Booking::whereIn('status', ['confirmed', 'checked_in'])->get();

        foreach ($bookings as $booking) {
            $paymentMethod = PaymentMethod::where('user_id', $booking->user_id)->first();
            
            if ($paymentMethod) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $booking->total_amount,
                    'currency' => 'SAR',
                    'status' => 'paid',
                    'type' => 'booking',
                    'description' => "Payment for booking {$booking->booking_code}",
                    'gateway_ref' => 'pay_' . strtoupper(\Illuminate\Support\Str::random(20)),
                    'paid_at' => $booking->created_at,
                ]);
            }
        }

        // Add a refund for cancelled booking
        $cancelledBooking = Booking::where('status', 'cancelled')->first();
        if ($cancelledBooking) {
            $paymentMethod = PaymentMethod::where('user_id', $cancelledBooking->user_id)->first();
            
            if ($paymentMethod) {
                Payment::create([
                    'booking_id' => $cancelledBooking->id,
                    'user_id' => $cancelledBooking->user_id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $cancelledBooking->total_amount,
                    'currency' => 'SAR',
                    'status' => 'refunded',
                    'type' => 'refund',
                    'description' => "Refund for cancelled booking {$cancelledBooking->booking_code}",
                    'gateway_ref' => 'ref_' . strtoupper(\Illuminate\Support\Str::random(20)),
                    'paid_at' => $cancelledBooking->cancelled_at,
                ]);
            }
        }

        // Add a pending payment
        $pendingBooking = Booking::where('status', 'pending')->first();
        if ($pendingBooking) {
            $paymentMethod = PaymentMethod::where('user_id', $pendingBooking->user_id)->first();
            
            if ($paymentMethod) {
                Payment::create([
                    'booking_id' => $pendingBooking->id,
                    'user_id' => $pendingBooking->user_id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $pendingBooking->total_amount,
                    'currency' => 'SAR',
                    'status' => 'pending',
                    'type' => 'booking',
                    'description' => "Payment for booking {$pendingBooking->booking_code}",
                    'gateway_ref' => 'pay_' . strtoupper(\Illuminate\Support\Str::random(20)),
                ]);
            }
        }

        $this->command->info('Payment methods and payments seeded successfully!');
    }
}
