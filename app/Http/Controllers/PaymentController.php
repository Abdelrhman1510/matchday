<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process payment for a booking
     * POST /api/v1/payments/process
     */
    public function process(ProcessPaymentRequest $request): JsonResponse
    {
        $booking = Booking::with(['payment', 'match'])->findOrFail($request->booking_id);
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        try {
            $payment = $this->paymentService->processPayment($booking, $paymentMethod);

            // TODO: In production, send notification here
            // event(new PaymentProcessed($payment));

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => new PaymentResource($payment),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 400);
        }
    }

    /**
     * Get payment history for authenticated user
     * GET /api/v1/payments/history
     */
    public function history(Request $request): JsonResponse
    {
        // Validate query parameters
        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'in:booking,subscription'],
            'status' => ['sometimes', 'string', 'in:pending,paid,failed,refunded'],
            'period' => ['sometimes', 'string', 'in:today,week,month,quarter,year'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = $this->paymentService->getPaymentHistory(
            $request->user(),
            $validated['type'] ?? null,
            $validated['status'] ?? null,
            $validated['period'] ?? null,
            $validated['per_page'] ?? 10
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment history retrieved successfully',
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Refund a payment
     * POST /api/v1/payments/{id}/refund
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Find payment - check ownership separately for 403
        $payment = Payment::with(['booking', 'paymentMethod'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        if ($payment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to refund this payment.',
            ], 403);
        }

        try {
            $refundedPayment = $this->paymentService->refundPayment($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => [
                    'payment_id' => $refundedPayment->id,
                    'status' => $refundedPayment->status,
                    'refund_amount' => (float) $refundedPayment->amount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 422);
        }
    }

    /**
     * Process payment for a booking (nested route)
     * POST /api/v1/bookings/{id}/payment
     */
    public function processBookingPayment(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
        ]);

        $booking = Booking::with(['payment', 'match'])->where('user_id', $user->id)->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // Check if already paid
        if ($booking->payment && in_array($booking->payment->status, ['paid', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking has already been paid.',
                'errors' => ['booking' => ['Payment already exists for this booking.']],
            ], 422);
        }

        $paymentMethod = PaymentMethod::where('user_id', $user->id)->find($validated['payment_method_id']);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        try {
            $payment = $this->paymentService->processPayment($booking, $paymentMethod);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => (float) $payment->amount,
                    'transaction_id' => $payment->gateway_ref ?? $payment->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'errors' => ['message' => $e->getMessage()],
            ], 400);
        }
    }
}
