<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;

/**
 * Real Moyasar gateway. Charges stored card-on-file tokens; card data is never
 * handled here. Amounts are recomputed by callers and passed in via $payment.
 */
class MoyasarGateway implements PaymentGatewayInterface
{
    public function __construct(private MoyasarClient $client) {}

    /**
     * Charge the token stored on the payment method.
     * Returns the usual contract plus, when the issuer forces 3DS:
     *   'requires_action' => true, 'transaction_url' => '…'
     */
    public function charge(Payment $payment, PaymentMethod $paymentMethod): array
    {
        $token = $paymentMethod->provider_token;
        if (!$token) {
            return ['success' => false, 'gateway_ref' => null, 'message' => 'This card can no longer be charged. Please add it again.'];
        }

        $amount = (int) round(((float) $payment->amount) * 100); // SAR -> halalas
        $currency = $payment->currency ?: 'SAR';

        try {
            $result = $this->client->createTokenPayment(
                $token,
                $amount,
                $currency,
                $payment->description ?: 'Shaj3 payment',
                ['payment_id' => (string) $payment->id],
                url('/payments/callback')
            );
        } catch (\Throwable $e) {
            return ['success' => false, 'gateway_ref' => null, 'message' => $e->getMessage()];
        }

        $status = $result['status'] ?? null;
        $id = $result['id'] ?? null;

        if (in_array($status, ['paid', 'captured'], true)) {
            return ['success' => true, 'gateway_ref' => $id, 'message' => 'Payment successful', 'status' => $status];
        }

        // Issuer demands 3DS — usually won't happen for a stored token.
        if ($status === 'initiated' && !empty($result['source']['transaction_url'])) {
            return [
                'success' => false,
                'gateway_ref' => $id,
                'message' => 'Additional verification is required to complete this payment.',
                'requires_action' => true,
                'transaction_url' => $result['source']['transaction_url'],
                'status' => $status,
            ];
        }

        $message = $result['source']['message'] ?? ($result['message'] ?? 'Payment was declined.');
        Log::warning('Moyasar token charge not completed', ['status' => $status, 'payment' => $id]);

        return ['success' => false, 'gateway_ref' => $id, 'message' => $message, 'status' => $status];
    }

    public function refund(Payment $payment): array
    {
        if (!$payment->gateway_ref) {
            return ['success' => false, 'gateway_ref' => null, 'message' => 'No gateway reference for this payment.'];
        }

        try {
            $result = $this->client->refundPayment($payment->gateway_ref);
        } catch (\Throwable $e) {
            return ['success' => false, 'gateway_ref' => null, 'message' => $e->getMessage()];
        }

        $status = $result['status'] ?? null;
        $ok = in_array($status, ['refunded', 'refunding'], true);

        return [
            'success' => $ok,
            'gateway_ref' => $result['id'] ?? $payment->gateway_ref,
            'message' => $ok ? 'Refund processed' : 'Refund failed',
        ];
    }

    public function verify(string $gatewayRef): array
    {
        try {
            $payment = $this->client->getPayment($gatewayRef);
        } catch (\Throwable $e) {
            return ['status' => 'unknown', 'message' => $e->getMessage()];
        }

        return ['status' => $payment['status'] ?? 'unknown', 'message' => null];
    }

    /**
     * Moyasar cards are tokenized client-side by the SDK, never server-side, so
     * this path is intentionally unsupported.
     */
    public function createPaymentMethodToken(array $cardData): array
    {
        return ['success' => false, 'token' => null, 'message' => 'Card tokenization happens in the app, not on the server.'];
    }
}
