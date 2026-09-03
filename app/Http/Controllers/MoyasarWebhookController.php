<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Records Moyasar payment events. The app is not a reliable reporter of its own
 * outcome (a payment completing after the user backgrounds the app must still be
 * recorded), so this is the source of truth for asynchronous results.
 */
class MoyasarWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.moyasar.webhook_secret');
        $provided = (string) $request->input('secret_token', '');

        if ($secret === '' || !hash_equals($secret, $provided)) {
            Log::warning('Moyasar webhook rejected: bad secret');
            return response()->json(['success' => false], 401);
        }

        $data = (array) $request->input('data', []);
        $moyasarId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $ourPaymentId = $data['metadata']['payment_id'] ?? null;

        // Match our own payment record: prefer the id we set in metadata, fall
        // back to the gateway reference.
        $payment = null;
        if ($ourPaymentId) {
            $payment = Payment::find($ourPaymentId);
        }
        if (!$payment && $moyasarId) {
            $payment = Payment::where('gateway_ref', $moyasarId)->first();
        }

        if (!$payment) {
            // Unknown payment (e.g. a card-verification hold) — acknowledge so
            // Moyasar stops retrying.
            return response()->json(['success' => true, 'message' => 'ignored'], 200);
        }

        $this->applyStatus($payment, $status, $moyasarId);

        return response()->json(['success' => true], 200);
    }

    private function applyStatus(Payment $payment, ?string $status, ?string $moyasarId): void
    {
        $target = match ($status) {
            'paid', 'captured' => 'paid',
            'failed' => 'failed',
            'refunded' => 'refunded',
            default => null,
        };

        if ($target === null || $payment->status === $target) {
            return; // nothing to do / already applied (idempotent)
        }

        $payment->update(array_filter([
            'status' => $target,
            'gateway_ref' => $moyasarId ?: $payment->gateway_ref,
            'paid_at' => $target === 'paid' ? now() : $payment->paid_at,
        ], fn ($v) => $v !== null));

        // A booking whose payment just completed asynchronously should confirm.
        if ($target === 'paid' && $payment->booking && $payment->booking->status === 'pending') {
            $payment->booking->update(['status' => 'confirmed']);
        }
    }
}
