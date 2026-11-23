<?php

namespace App\Services\Wallet;

use App\Events\BookingCreated;
use App\Jobs\RefundBookingPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\Payment\MoyasarClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The booking side of the money flow: paid → confirmed, check-in, expiry, refund.
 * Every entry point is idempotent, because the charge response, the webhook and
 * the reconcile job can all report the same payment.
 */
class BookingPaymentService
{
    public function __construct(
        private WalletService $wallet,
        private FeeSettingsService $fees,
    ) {}

    /** @return bool true when this call is the one that confirmed the booking */
    public function confirmPaid(Booking $booking, Payment $payment): bool
    {
        return DB::transaction(function () use ($booking, $payment) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if ($booking->payment_status === 'paid') {
                return false;
            }

            if ($booking->status === 'cancelled' && !$this->reclaimSeats($booking)) {
                $this->queueRefund($payment);
                return false;
            }

            $wasPayAtCafe = $booking->payment_method === 'pay_at_cafe';
            $gatewayFee = $this->gatewayFee($booking, $payment);
            $booking->fill([
                'status' => in_array($booking->status, ['checked_in', 'completed'], true) ? $booking->status : 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'online',
                'payment_expires_at' => null,
                'paid_at' => $payment->paid_at ?? now(),
                'cancelled_at' => null,
                'cancel_reason' => null,
                'gateway_fee' => $gatewayFee,
                'cafe_net_amount' => round((float) $booking->subtotal - (float) $booking->commission_amount - $gatewayFee, 2),
            ]);

            $bookingService = app(BookingService::class);
            if (!$booking->qr_code) {
                $booking->qr_code = $bookingService->generateQrCode($booking);
            }
            $booking->save();

            $this->wallet->recordBookingEarning($booking);
            $this->awardPoints($booking, $bookingService);

            if (!$wasPayAtCafe) {
                DB::afterCommit(function () use ($booking) {
                    try {
                        event(new BookingCreated($booking->fresh()));
                    } catch (\Throwable $e) {
                        Log::warning("Booking confirmed notification failed for {$booking->id}: {$e->getMessage()}");
                    }
                });
            }

            return true;
        });
    }

    /** Null when check-in is allowed, otherwise the user-facing reason. */
    public function checkInBlockedReason(Booking $booking): ?string
    {
        return $booking->payment_method === 'online' && $booking->payment_status !== 'paid'
            ? __('wallet.errors.booking_not_paid')
            : null;
    }

    public function checkIn(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $changes = ['status' => 'checked_in', 'checked_in_at' => now()];
            $collectedAtCafe = $booking->payment_method === 'pay_at_cafe' && $booking->payment_status !== 'paid';
            if ($collectedAtCafe) {
                $changes['payment_status'] = 'paid_at_cafe';
                $changes['paid_at'] = now();
            }
            $booking->update($changes);

            if ($collectedAtCafe) {
                $this->awardPoints($booking, app(BookingService::class));
            }
            $this->wallet->onCheckIn($booking);

            return $booking;
        });
    }

    /** @return int number of bookings expired */
    public function expireUnpaid(): int
    {
        $ids = Booking::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now())
            ->pluck('id');

        $expired = 0;
        foreach ($ids as $id) {
            $expired += DB::transaction(function () use ($id) {
                $booking = Booking::whereKey($id)->lockForUpdate()->first();
                // Re-check under the lock: the payment may have landed meanwhile.
                if (!$booking || $booking->status !== 'pending' || $booking->payment_status !== 'unpaid') {
                    return 0;
                }

                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancel_reason' => 'payment_timeout',
                ]);
                $booking->match()->increment('seats_available', $booking->seats()->count());

                return 1;
            });
        }

        return $expired;
    }

    /** Money-side of any cancellation: reverse the ledger and refund a real charge. */
    public function handleCancellation(Booking $booking): void
    {
        $this->wallet->reverseBooking($booking);

        // Read from the payment row: bookings older than this flow have no payment_status.
        $payment = $booking->payment()->first();
        if (!$payment || !in_array($payment->status, ['paid', 'completed'], true)) {
            return;
        }

        $booking->update(['payment_status' => 'refunded']);
        if ($payment->gateway_ref) {
            $this->queueRefund($payment);
        }
    }

    /** Moyasar reports a refund/void that may have been made outside our app. */
    public function handleRemoteRefund(Payment $payment): void
    {
        $booking = $payment->booking()->lockForUpdate()->first();
        if (!$booking) {
            return;
        }

        $this->wallet->reverseBooking($booking);

        $changes = ['payment_status' => 'refunded'];
        if (in_array($booking->status, ['pending', 'confirmed'], true)) {
            $changes += ['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => 'refunded'];
            $booking->match()->increment('seats_available', $booking->seats()->count());
        }
        $booking->update($changes);
    }

    /** A payment landed after expiry: keep the booking only if its seats are still free. */
    private function reclaimSeats(Booking $booking): bool
    {
        if ($booking->cancel_reason !== 'payment_timeout') {
            return false;
        }

        $seatIds = $booking->seats()->pluck('seats.id');
        $taken = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->where('bookings.match_id', $booking->match_id)
            ->where('bookings.id', '!=', $booking->id)
            ->whereNull('bookings.deleted_at')
            ->whereIn('bookings.status', ['pending', 'confirmed', 'checked_in'])
            ->whereIn('booking_seats.seat_id', $seatIds)
            ->exists();
        if ($taken) {
            return false;
        }

        $booking->match()->decrement('seats_available', $seatIds->count());

        return true;
    }

    private function queueRefund(Payment $payment): void
    {
        DB::afterCommit(function () use ($payment) {
            try {
                RefundBookingPayment::dispatch($payment->id);
            } catch (\Throwable $e) {
                Log::critical("Automatic refund failed for payment {$payment->id}: {$e->getMessage()}");
            }
        });
    }

    private function awardPoints(Booking $booking, BookingService $bookingService): void
    {
        try {
            $bookingService->awardLoyaltyPoints($booking->user, $booking);
        } catch (\Throwable $e) {
            Log::warning("Loyalty award failed for booking {$booking->id}: {$e->getMessage()}");
        }
    }

    /** Moyasar's fee for this charge, when the settings say the cafe bears it. */
    private function gatewayFee(Booking $booking, Payment $payment): float
    {
        $bearer = $booking->fees_snapshot['gateway_fee']['bearer'] ?? 'platform';
        if ($bearer !== 'cafe' || !$payment->gateway_ref) {
            return 0.0;
        }

        try {
            $remote = app(MoyasarClient::class)->getPayment($payment->gateway_ref);

            return round(((int) ($remote['fee'] ?? 0)) / 100, 2);
        } catch (\Throwable $e) {
            Log::warning("Could not read Moyasar fee for payment {$payment->id}: {$e->getMessage()}");

            return 0.0;
        }
    }
}
