<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\MoyasarClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records Moyasar payment events. The app is not a reliable reporter of its own
 * outcome (a payment completing after the user backgrounds the app must still be
 * recorded), so this is the source of truth for asynchronous results.
 *
 * Moyasar authenticates webhooks with the shared secret in the body's
 * `secret_token`. The event body is only a hint: the status applied is always
 * re-read from Moyasar's API, so a forged or out-of-order event cannot change
 * a payment to a state Moyasar doesn't report.
 */
class MoyasarWebhookController extends Controller
{
    public function handle(Request $request, MoyasarClient $moyasar): JsonResponse
    {
        $secret = (string) config('services.moyasar.webhook_secret');
        $provided = (string) $request->input('secret_token', '');

        if ($secret === '' || !hash_equals($secret, $provided)) {
            Log::warning('Moyasar webhook rejected: bad secret');
            return response()->json(['success' => false], 401);
        }

        $moyasarId = $request->input('data.id');
        if (!is_string($moyasarId) || $moyasarId === '') {
            return response()->json(['success' => true, 'message' => 'ignored'], 200);
        }

        $payment = $this->findPayment($moyasarId, $request->input('data.metadata.payment_id'));
        if (!$payment) {
            // Unknown payment (e.g. a card-verification hold) — acknowledge so
            // Moyasar stops retrying.
            return response()->json(['success' => true, 'message' => 'ignored'], 200);
        }

        try {
            $remote = $moyasar->getPayment($moyasarId);
        } catch (\Throwable $e) {
            // Non-2xx makes Moyasar retry later.
            Log::warning("Moyasar webhook: could not verify payment for our payment {$payment->id}");
            return response()->json(['success' => false], 503);
        }

        // The remote payment must be the one we charged, for the amount we charged.
        $amountMatches = (int) ($remote['amount'] ?? -1) === (int) round($payment->amount * 100)
            && strtoupper((string) ($remote['currency'] ?? '')) === strtoupper((string) $payment->currency);
        if (($remote['id'] ?? null) !== $moyasarId || !$amountMatches) {
            Log::warning("Moyasar webhook: payment {$payment->id} does not match the Moyasar record; ignored");
            return response()->json(['success' => true, 'message' => 'ignored'], 200);
        }

        $this->applyStatus($payment, $remote['status'] ?? null, $moyasarId);

        return response()->json(['success' => true], 200);
    }

    private function findPayment(string $moyasarId, $ourPaymentId): ?Payment
    {
        $byRef = Payment::where('gateway_ref', $moyasarId)->first();
        if ($byRef) {
            return $byRef;
        }

        // Fall back to the id we put in metadata, but only for a row not yet tied
        // to a different Moyasar payment.
        $byMeta = is_numeric($ourPaymentId) ? Payment::find((int) $ourPaymentId) : null;

        return $byMeta && ($byMeta->gateway_ref === null || $byMeta->gateway_ref === $moyasarId) ? $byMeta : null;
    }

    private function applyStatus(Payment $payment, ?string $remoteStatus, string $moyasarId): void
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
            return;
        }

        DB::transaction(function () use ($payment, $target, $moyasarId, $paidStatus) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $current = $payment->status;

            $alreadyApplied = $current === $target
                || ($target === $paidStatus && in_array($current, ['paid', 'completed'], true));
            // Refunded is final; a paid payment never goes back to failed.
            $wouldRegress = $current === 'refunded'
                || ($target === 'failed' && in_array($current, ['paid', 'completed'], true));
            if ($alreadyApplied || $wouldRegress) {
                return; // duplicate or out-of-order delivery
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
        });
    }
}
