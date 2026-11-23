<?php

namespace App\Jobs;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Returns a customer's money for a booking that can no longer be honoured. */
class RefundBookingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public int $paymentId) {}

    public function handle(PaymentGatewayInterface $gateway): void
    {
        $payment = Payment::find($this->paymentId);
        if (!$payment || $payment->status === 'refunded' || !$payment->gateway_ref) {
            return;
        }

        $result = $gateway->refund($payment);
        if (!$result['success']) {
            Log::critical("Refund failed for payment {$payment->id}: " . ($result['message'] ?? 'unknown'));
            throw new \RuntimeException('Refund failed; will retry.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'refunded']);
            $payment->booking?->update(['payment_status' => 'refunded']);
        });
    }
}
