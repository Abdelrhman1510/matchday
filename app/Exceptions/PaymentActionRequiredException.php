<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The card issuer requires 3DS before a saved-card charge can complete. The
 * customer must open $transactionUrl; the webhook records the outcome. The
 * message is customer-readable — the app shows it verbatim.
 */
class PaymentActionRequiredException extends RuntimeException
{
    public function __construct(
        public readonly string $transactionUrl,
        public readonly int $paymentId,
        string $message = 'Your bank needs to verify this payment. Complete the verification to finish.',
    ) {
        parent::__construct($message);
    }

    /** Response body shared by payments/process and subscription/upgrade. */
    public function toResponseArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'requires_action' => true,
                'transaction_url' => $this->transactionUrl,
                'payment_id' => $this->paymentId,
            ],
        ];
    }
}
