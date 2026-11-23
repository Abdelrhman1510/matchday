<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Payment\MoyasarPaymentSync;
use Illuminate\Console\Command;

class ReconcileMoyasarPayments extends Command
{
    protected $signature = 'payments:reconcile';

    protected $description = 'Settle pending Moyasar charges (e.g. 3DS finished but the webhook never arrived) from Moyasar\'s own record';

    public function handle(MoyasarPaymentSync $sync): int
    {
        // Only charges that reached Moyasar; 3DS sessions don't outlive a couple of days.
        $pending = Payment::where('status', 'pending')
            ->whereNotNull('gateway_ref')
            ->where('created_at', '>', now()->subDays(3))
            ->where('created_at', '<', now()->subMinutes(5)) // give the webhook a head start
            ->get();

        $applied = 0;
        foreach ($pending as $payment) {
            try {
                $applied += $sync->sync($payment, $payment->gateway_ref) === 'applied' ? 1 : 0;
            } catch (\Throwable $e) {
                $this->warn("Payment {$payment->id}: Moyasar unreachable, will retry next run.");
            }
        }

        // Webhook events whose follow-up failed (Moyasar unreachable at the time).
        \App\Models\MoyasarWebhookEvent::whereNull('processed_at')
            ->where('created_at', '>', now()->subDays(3))
            ->where('created_at', '<', now()->subMinutes(5))
            ->pluck('id')
            ->each(fn ($id) => \App\Jobs\ProcessMoyasarWebhookEvent::dispatchSync($id));

        // Money still held for a cancelled booking: a refund that failed earlier.
        // cancel_reason and payment_method exist only on bookings from the wallet flow,
        // so older cancelled bookings can never be refunded by this job.
        $owed = Payment::where('type', 'booking')
            ->whereIn('status', ['paid', 'completed'])
            ->whereNotNull('gateway_ref')
            ->whereHas('booking', function ($q) {
                $q->where('status', 'cancelled')->whereNotNull('cancel_reason')->whereNotNull('payment_method');
            })
            ->pluck('id');
        foreach ($owed as $paymentId) {
            try {
                \App\Jobs\RefundBookingPayment::dispatchSync($paymentId);
            } catch (\Throwable $e) {
                $this->warn("Payment {$paymentId}: refund still failing, will retry next run.");
            }
        }

        $this->info("Checked {$pending->count()} pending payment(s), settled {$applied}, retried {$owed->count()} refund(s).");

        return Command::SUCCESS;
    }
}
