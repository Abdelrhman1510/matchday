<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Brings one of our payments in line with Moyasar's own record of it. Used by the
 * webhook, the 3DS return page and the reconcile job, so a delayed or missing
 * webhook still settles the payment. Status is always re-read from Moyasar's API;
 * callers never pass a status in.
 */
class MoyasarPaymentSync
{
    public function __construct(private MoyasarClient $moyasar) {}

    /**
     * Find our payment for a Moyasar id: by gateway_ref, else by the id we put in
     * metadata, but only for a row not yet tied to a different Moyasar payment.
     */
    public function findPayment(string $moyasarId, $ourPaymentId = null): ?Payment
    {
        $byRef = Payment::where('gateway_ref', $moyasarId)->first();
        if ($byRef) {
            return $byRef;
        }

        $byMeta = is_numeric($ourPaymentId) ? Payment::find((int) $ourPaymentId) : null;

        return $byMeta && ($byMeta->gateway_ref === null || $byMeta->gateway_ref === $moyasarId) ? $byMeta : null;
    }

    /**
     * @return string 'applied' | 'unchanged' | 'mismatch'
     * @throws \Throwable when Moyasar can't be reached (callers retry later)
     */
    public function sync(Payment $payment, string $moyasarId): string
    {
        $remote = $this->moyasar->getPayment($moyasarId);

        // The remote payment must be the one we charged, for the amount we charged.
        $amountMatches = (int) ($remote['amount'] ?? -1) === (int) round($payment->amount * 100)
            && strtoupper((string) ($remote['currency'] ?? '')) === strtoupper((string) $payment->currency);
        // A row matched only via metadata must be the one Moyasar's own record names —
        // otherwise any paid payment could settle another row.
        $boundToUs = $payment->gateway_ref === $moyasarId
            || (string) ($remote['metadata']['payment_id'] ?? '') === (string) $payment->id;
        if (($remote['id'] ?? null) !== $moyasarId || !$amountMatches || !$boundToUs) {
            Log::warning("Moyasar sync: payment {$payment->id} does not match the Moyasar record; ignored");
            return 'mismatch';
        }

        return $this->applyStatus($payment, $remote['status'] ?? null, $moyasarId) ? 'applied' : 'unchanged';
    }

    private function applyStatus(Payment $payment, ?string $remoteStatus, string $moyasarId): bool
    {
        // Booking charges are stored as 'completed', subscription charges as 'paid'.
        $paidStatus = $payment->type === 'booking' ? 'completed' : 'paid';

        $target = match ($remoteStatus) {
            'paid', 'captured' => $paidStatus,
            'failed' => 'failed',
            'refunded' => 'refunded',
            default => null, // initiated/authorized/voided: nothing to record here
        };
        if ($target === null) {
            return false;
        }

        return DB::transaction(function () use ($payment, $target, $moyasarId, $paidStatus) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $current = $payment->status;

            $alreadyApplied = $current === $target
                || ($target === $paidStatus && in_array($current, ['paid', 'completed'], true));
            // Refunded is final; a paid payment never goes back to failed.
            $wouldRegress = $current === 'refunded'
                || ($target === 'failed' && in_array($current, ['paid', 'completed'], true));
            if ($alreadyApplied || $wouldRegress) {
                return false; // duplicate or out-of-order delivery
            }

            $payment->update([
                'status' => $target,
                'gateway_ref' => $moyasarId,
                'paid_at' => $target === $paidStatus ? ($payment->paid_at ?? now()) : $payment->paid_at,
            ]);

            // A booking whose payment just completed asynchronously should confirm.
            if ($target === $paidStatus && $payment->booking && $payment->booking->status === 'pending') {
                $payment->booking->update(['status' => 'confirmed']);
            }

            // A subscription upgrade/renewal that needed 3DS takes effect now.
            if ($target === $paidStatus && $payment->type === 'subscription') {
                app(SubscriptionService::class)->completeSubscriptionPayment($payment);
            }

            return true;
        });
    }
}
