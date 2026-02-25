<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all payment methods for authenticated user
     * GET /api/v1/payment-methods
     */
    public function index(Request $request): JsonResponse
    {
        $paymentMethods = $this->paymentService->getUserPaymentMethods($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Payment methods retrieved successfully',
            'data' => PaymentMethodResource::collection($paymentMethods),
        ]);
    }

    /**
     * Create a new payment method
     * POST /api/v1/payment-methods
     */
    public function store(StorePaymentMethodRequest $request): JsonResponse
    {
        try {
            $paymentMethod = $this->paymentService->createPaymentMethod(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment method created successfully',
                'data' => new PaymentMethodResource($paymentMethod),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment method',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Update a payment method
     * PUT /api/v1/payment-methods/{id}
     */
    public function update(UpdatePaymentMethodRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        // Find payment method
        $paymentMethod = \App\Models\PaymentMethod::where('user_id', $user->id)
            ->findOrFail($id);

        try {
            $paymentMethod = $this->paymentService->updatePaymentMethod(
                $paymentMethod,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
                'data' => new PaymentMethodResource($paymentMethod),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Delete a payment method
     * DELETE /api/v1/payment-methods/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        // Find payment method
        $paymentMethod = \App\Models\PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        if ($paymentMethod->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to payment method',
            ], 403);
        }

        try {
            $this->paymentService->deletePaymentMethod($paymentMethod);

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment method',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 400);
        }
    }

    /**
     * Set payment method as primary
     * PUT /api/v1/payment-methods/{id}/primary
     */
    public function setPrimary(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        
        // Find payment method
        $paymentMethod = \App\Models\PaymentMethod::where('user_id', $user->id)
            ->findOrFail($id);

        try {
            $paymentMethod = $this->paymentService->setPrimaryPaymentMethod($paymentMethod);

            return response()->json([
                'success' => true,
                'message' => 'Payment method set as primary successfully',
                'data' => new PaymentMethodResource($paymentMethod),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary payment method',
                'data' => [],
                'errors' => ['message' => $e->getMessage()],
            ], 500);
        }
    }
}
