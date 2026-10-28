<?php

namespace App\Http\Controllers;

use App\Services\Payment\MoyasarPaymentSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function handle(Request $request, MoyasarPaymentSync $sync): JsonResponse
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

        $payment = $sync->findPayment($moyasarId, $request->input('data.metadata.payment_id'));
        if (!$payment) {
            // Unknown payment (e.g. a card-verification hold) — acknowledge so
            // Moyasar stops retrying.
            return response()->json(['success' => true, 'message' => 'ignored'], 200);
        }

        try {
            $result = $sync->sync($payment, $moyasarId);
        } catch (\Throwable $e) {
            // Non-2xx makes Moyasar retry later.
            Log::warning("Moyasar webhook: could not verify payment for our payment {$payment->id}");
            return response()->json(['success' => false], 503);
        }

        return $result === 'mismatch'
            ? response()->json(['success' => true, 'message' => 'ignored'], 200)
            : response()->json(['success' => true], 200);
    }
}
