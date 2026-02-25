<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\PaymentMethod;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     *
     * @param Payment $payment
     * @param PaymentMethod $paymentMethod
     * @return array ['success' => bool, 'gateway_ref' => string|null, 'message' => string|null]
     */
    public function charge(Payment $payment, PaymentMethod $paymentMethod): array;

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @return array ['success' => bool, 'gateway_ref' => string|null, 'message' => string|null]
     */
    public function refund(Payment $payment): array;

    /**
     * Verify payment status
     *
     * @param string $gatewayRef
     * @return array ['status' => string, 'message' => string|null]
     */
    public function verify(string $gatewayRef): array;

    /**
     * Create a payment method token (for card tokenization)
     *
     * @param array $cardData
     * @return array ['success' => bool, 'token' => string|null, 'message' => string|null]
     */
    public function createPaymentMethodToken(array $cardData): array;
}
