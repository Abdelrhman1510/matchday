<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Simulate payment processing
     * In production, this would call actual payment gateway API (Stripe, Tap, etc.)
     */
    public function charge(Payment $payment, PaymentMethod $paymentMethod): array
    {
        // Simulate API call delay
        usleep(500000); // 0.5 second

        // Simulate success for most payments
        // Fail if card_last_four is '0000' (for testing)
        if ($paymentMethod->card_last_four === '0000') {
            return [
                'success' => false,
                'gateway_ref' => null,
                'message' => 'Card declined - insufficient funds',
            ];
        }

        // Simulate successful payment
        return [
            'success' => true,
            'gateway_ref' => 'sim_' . Str::random(24), // Simulated transaction reference
            'message' => 'Payment processed successfully',
        ];
    }

    /**
     * Simulate payment refund
     */
    public function refund(Payment $payment): array
    {
        // Simulate API call delay
        usleep(500000); // 0.5 second

        // Check if payment has a gateway reference
        if (!$payment->gateway_ref) {
            return [
                'success' => false,
                'gateway_ref' => null,
                'message' => 'No gateway reference found for this payment',
            ];
        }

        // Simulate successful refund
        return [
            'success' => true,
            'gateway_ref' => 'ref_' . Str::random(24), // Simulated refund reference
            'message' => 'Refund processed successfully',
        ];
    }

    /**
     * Simulate payment verification
     */
    public function verify(string $gatewayRef): array
    {
        // Simulate API call delay
        usleep(300000); // 0.3 seconds

        // Simulate verification
        if (str_starts_with($gatewayRef, 'sim_')) {
            return [
                'status' => 'paid',
                'message' => 'Payment verified successfully',
            ];
        }

        if (str_starts_with($gatewayRef, 'ref_')) {
            return [
                'status' => 'refunded',
                'message' => 'Refund verified successfully',
            ];
        }

        return [
            'status' => 'unknown',
            'message' => 'Gateway reference not found',
        ];
    }

    /**
     * Simulate creating a payment method token
     * In production, this would tokenize card details via payment gateway
     */
    public function createPaymentMethodToken(array $cardData): array
    {
        // Simulate API call delay
        usleep(400000); // 0.4 seconds

        // Validate card number (basic simulation)
        if (empty($cardData['card_number']) || strlen($cardData['card_number']) < 13) {
            return [
                'success' => false,
                'token' => null,
                'message' => 'Invalid card number',
            ];
        }

        // Simulate successful tokenization
        return [
            'success' => true,
            'token' => 'tok_' . Str::random(32), // Simulated token
            'message' => 'Card tokenized successfully',
        ];
    }
}
