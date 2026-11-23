<?php

namespace App\Jobs;

use App\Models\MoyasarWebhookEvent;
use App\Services\Payment\MoyasarPaymentSync;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Never trusts the event body: MoyasarPaymentSync re-reads the payment from Moyasar.
 * If that fails the event stays unprocessed and payments:reconcile picks it up.
 */
class ProcessMoyasarWebhookEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SETTLING = [
        'payment_paid', 'payment_captured', 'payment_failed', 'payment_abandoned',
        'payment_refunded', 'payment_voided',
    ];

    public function __construct(public int $eventRowId) {}

    public function handle(MoyasarPaymentSync $sync): void
    {
        $event = MoyasarWebhookEvent::find($this->eventRowId);
        if (!$event || $event->processed_at) {
            return;
        }

        if (in_array($event->type, self::SETTLING, true)) {
            $moyasarId = $event->payload['data']['id'] ?? null;
            $payment = is_string($moyasarId)
                ? $sync->findPayment($moyasarId, $event->payload['data']['metadata']['payment_id'] ?? null)
                : null;

            if ($payment) {
                try {
                    $sync->sync($payment, $moyasarId);
                } catch (\Throwable $e) {
                    Log::warning("Moyasar event {$event->event_id}: could not verify payment, will retry");
                    return;
                }
            }
        }

        $event->update(['processed_at' => now()]);
    }
}
