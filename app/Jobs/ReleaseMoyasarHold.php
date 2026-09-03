<?php

namespace App\Jobs;

use App\Services\Payment\MoyasarClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Releases the SAR 1 card-verification hold AFTER the card is stored. A stored
 * card with a stuck hold is recoverable (retried here); a released hold with no
 * stored card would mean the customer did all this for nothing — so this runs
 * only once the payment method exists.
 *
 * ponytail: real retries need a durable queue (QUEUE_CONNECTION=database). On the
 * sync connection this runs inline; a failure is caught at dispatch so the store
 * still succeeds, and the hold is swept up by re-running / manual void.
 */
class ReleaseMoyasarHold implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(
        public string $paymentId,
        public string $mode // 'void' (authorized) or 'refund' (auto-captured "paid")
    ) {}

    public function handle(MoyasarClient $client): void
    {
        if ($this->mode === 'refund') {
            $client->refundPayment($this->paymentId);
        } else {
            $client->voidPayment($this->paymentId);
        }

        Log::info('Moyasar verification hold released', [
            'payment_id' => $this->paymentId,
            'mode' => $this->mode,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Moyasar hold release exhausted retries — hold still open', [
            'payment_id' => $this->paymentId,
            'mode' => $this->mode,
            'error' => $e->getMessage(),
        ]);
    }
}
