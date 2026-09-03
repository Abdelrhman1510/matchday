<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Process payment for a booking
     */
    public function processPayment(Booking $booking, PaymentMethod $paymentMethod): Payment
    {
        return DB::transaction(function () use ($booking, $paymentMethod) {
            $payment = $booking->payment;

            // Create payment record if it doesn't exist
            if (!$payment) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $booking->total_price ?? $booking->total_amount ?? 0,
                    'currency' => $booking->currency ?? 'SAR',
                    'status' => 'pending',
                    'type' => 'booking',
                    'description' => 'Payment for booking ' . $booking->booking_code,
                ]);
            } else {
                // Update payment with payment method
                $payment->update([
                    'payment_method_id' => $paymentMethod->id,
                ]);
            }

            // Attempt to charge via gateway
            $result = $this->gateway->charge($payment, $paymentMethod);

            if ($result['success']) {
                // Payment successful
                $payment->update([
                    'status' => 'completed',
                    'gateway_ref' => $result['gateway_ref'],
                    'paid_at' => now(),
                ]);

                // Update booking status to confirmed
                $booking->update([
                    'status' => 'confirmed',
                ]);

                return $payment->fresh(['booking', 'paymentMethod']);
            } else {
                // Payment failed
                $payment->update([
                    'status' => 'failed',
                    'description' => $result['message'],
                ]);

                throw new \Exception($result['message'] ?? 'Payment processing failed');
            }
        });
    }

    /**
     * Get payment history for a user
     */
    public function getPaymentHistory(
        User $user,
        ?string $type = null,
        ?string $status = null,
        ?string $period = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Payment::where('user_id', $user->id)
            ->with(['booking.match.homeTeam', 'booking.match.awayTeam', 'paymentMethod'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by period
        if ($period) {
            $startDate = match ($period) {
                'today' => now()->startOfDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'quarter' => now()->subQuarter(),
                'year' => now()->subYear(),
                default => null,
            };

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            // Check if already refunded
            if ($payment->status === 'refunded') {
                throw new \Exception('Payment has already been refunded');
            }

            // Verify payment is paid or completed
            if (!in_array($payment->status, ['paid', 'completed'])) {
                throw new \Exception('Only paid payments can be refunded');
            }

            // Attempt refund via gateway
            $result = $this->gateway->refund($payment);

            if ($result['success']) {
                // Refund successful
                $payment->update([
                    'status' => 'refunded',
                    'gateway_ref' => $result['gateway_ref'], // Update with refund reference
                ]);

                // Update related booking if exists
                if ($payment->booking) {
                    $payment->booking->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                    // Release seats
                    $seatsCount = $payment->booking->seats()->count();
                    $payment->booking->match->increment('seats_available', $seatsCount);
                }

                return $payment->fresh(['booking', 'paymentMethod']);
            } else {
                throw new \Exception($result['message'] ?? 'Refund processing failed');
            }
        });
    }

    /**
     * Get user's payment methods
     */
    public function getUserPaymentMethods(User $user): Collection
    {
        return PaymentMethod::where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a payment method
     */
    public function createPaymentMethod(User $user, array $data): PaymentMethod
    {
        return DB::transaction(function () use ($user, $data) {
            // If this is marked as primary or user has no payment methods, set as primary
            $isPrimary = $data['is_primary'] ?? false;
            
            if (!$isPrimary) {
                $existingMethod = PaymentMethod::where('user_id', $user->id)->first();
                if (!$existingMethod) {
                    $isPrimary = true; // First payment method is always primary
                }
            }

            // If setting as primary, unset other primary methods
            if ($isPrimary) {
                PaymentMethod::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Create payment method
            $cardLastFour = $data['card_last_four'] ?? null;
            if (!$cardLastFour && !empty($data['card_number'])) {
                $cardLastFour = substr($data['card_number'], -4);
            }

            // Build expires_at from expiry_month/expiry_year if not set
            $expiresAt = $data['expires_at'] ?? null;
            if (!$expiresAt && !empty($data['expiry_month']) && !empty($data['expiry_year'])) {
                $expiresAt = $data['expiry_year'] . '-' . $data['expiry_month'];
            }

            return PaymentMethod::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'card_last_four' => $cardLastFour,
                'card_holder' => $data['card_holder'] ?? null,
                'expires_at' => $expiresAt,
                'is_primary' => $isPrimary,
                'provider_token' => $data['provider_token'] ?? null,
            ]);
        });
    }

    /**
     * Verify a Moyasar card-on-file authorization and store the tokenized card.
     *
     * Card data never reaches us: the app authorizes SAR 1 (manual, save_card)
     * directly with Moyasar and sends only { payment_id, token }. We independently
     * verify that payment with our secret key, read authoritative card metadata
     * from the token, store it, then release the SAR 1 hold.
     *
     * @throws \App\Exceptions\PaymentMethodException with a customer-readable message
     */
    public function createFromMoyasar(User $user, array $data): PaymentMethod
    {
        $client = app(\App\Services\Payment\MoyasarClient::class);
        $paymentId = $data['payment_id'];
        $token = $data['token'];

        try {
            $payment = $client->getPayment($paymentId);
        } catch (\Throwable $e) {
            throw new \App\Exceptions\PaymentMethodException('We could not verify your card. Please try again.');
        }

        // Do not trust the client — verify the authorization ourselves.
        $status = $payment['status'] ?? null;
        if (!in_array($status, ['authorized', 'paid'], true)) {
            throw new \App\Exceptions\PaymentMethodException('Card verification was not approved. Please try another card.');
        }
        if (($payment['amount'] ?? null) !== 100 || ($payment['currency'] ?? null) !== 'SAR') {
            throw new \App\Exceptions\PaymentMethodException('This card verification is invalid. Please try again.');
        }
        if (($payment['metadata']['purpose'] ?? null) !== 'card_verification') {
            throw new \App\Exceptions\PaymentMethodException('This card verification is invalid. Please try again.');
        }
        // The token must belong to THIS payment — stops attaching someone else's token.
        if (($payment['source']['token'] ?? null) !== $token) {
            throw new \App\Exceptions\PaymentMethodException('This card verification is invalid. Please try again.');
        }
        // One authorization can attach exactly one card — stops replay.
        if (PaymentMethod::where('provider_payment_id', $paymentId)->exists()) {
            throw new \App\Exceptions\PaymentMethodException('This card has already been added.');
        }

        // Authoritative card metadata (the payment's card source omits the expiry).
        try {
            $tokenData = $client->getToken($token);
        } catch (\Throwable $e) {
            throw new \App\Exceptions\PaymentMethodException('We could not read your card details. Please try again.');
        }

        $month = str_pad((string) ($tokenData['month'] ?? ''), 2, '0', STR_PAD_LEFT);
        $year = (string) ($tokenData['year'] ?? '');
        $expiresAt = ($year !== '' && trim($month) !== '') ? "{$year}-{$month}" : null;
        $type = ($tokenData['funding'] ?? 'credit') === 'debit' ? 'debit_card' : 'credit_card';

        $paymentMethod = DB::transaction(function () use ($user, $data, $token, $paymentId, $tokenData, $type, $expiresAt) {
            $isPrimary = (bool) ($data['is_primary'] ?? false);
            if (!$isPrimary && !PaymentMethod::where('user_id', $user->id)->exists()) {
                $isPrimary = true; // first card is primary
            }
            if ($isPrimary) {
                PaymentMethod::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return PaymentMethod::create([
                'user_id' => $user->id,
                'type' => $type,
                'card_last_four' => $tokenData['last_four'] ?? null,
                'card_brand' => $tokenData['brand'] ?? null,
                'card_holder' => $tokenData['name'] ?? null,
                'expires_at' => $expiresAt,
                'is_primary' => $isPrimary,
                'provider_token' => $token,
                'provider_payment_id' => $paymentId,
            ]);
        });

        // Release the hold AFTER the card is stored. Retryable; a dispatch failure
        // on the sync connection must not undo an already-stored card.
        try {
            \App\Jobs\ReleaseMoyasarHold::dispatch($paymentId, $status === 'paid' ? 'refund' : 'void');
        } catch (\Throwable $e) {
            \Log::error('Failed to dispatch Moyasar hold release', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }

        return $paymentMethod;
    }

    /**
     * Update a payment method
     */
    public function updatePaymentMethod(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        $paymentMethod->update($data);
        return $paymentMethod->fresh();
    }

    /**
     * Delete a payment method
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): void
    {
        // Check if payment method is used in any non-refunded payments
        $activePayments = Payment::where('payment_method_id', $paymentMethod->id)
            ->whereIn('status', ['pending', 'paid'])
            ->exists();

        if ($activePayments) {
            throw new \Exception('Cannot delete payment method with active payments');
        }

        // If primary, set another method as primary
        if ($paymentMethod->is_primary) {
            $nextMethod = PaymentMethod::where('user_id', $paymentMethod->user_id)
                ->where('id', '!=', $paymentMethod->id)
                ->first();

            if ($nextMethod) {
                $nextMethod->update(['is_primary' => true]);
            }
        }

        $paymentMethod->delete();
    }

    /**
     * Set payment method as primary
     */
    public function setPrimaryPaymentMethod(PaymentMethod $paymentMethod): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod) {
            // Unset other primary methods for this user
            PaymentMethod::where('user_id', $paymentMethod->user_id)
                ->where('is_primary', true)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_primary' => false]);

            // Set this as primary
            $paymentMethod->update(['is_primary' => true]);

            return $paymentMethod->fresh();
        });
    }
}
