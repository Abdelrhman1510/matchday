<?php

namespace App\Http\Controllers;

use App\Http\Resources\BillingTransactionResource;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BillingController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * 6. GET /api/v1/cafe-admin/billing
     * Get transaction history with filters
     */
    public function index(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:all_time,this_month,last_3_months',
            'type' => 'sometimes|in:booking,subscription,cafe_order',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $transactions = $this->billingService->getTransactionHistory(
            $cafe,
            $request->get('period', 'all_time'),
            $request->get('type')
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction history retrieved successfully',
            'data' => BillingTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * 7. GET /api/v1/cafe-admin/billing/summary
     * Get billing summary
     */
    public function summary(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $summary = $this->billingService->getBillingSummary($cafe);

        return response()->json([
            'success' => true,
            'message' => 'Billing summary retrieved successfully',
            'data' => $summary,
        ]);
    }

    /**
     * 8. GET /api/v1/cafe-admin/billing/export
     * Export billing history as CSV
     */
    public function export(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'period' => 'sometimes|in:all_time,this_month,last_3_months',
            'type' => 'sometimes|in:booking,subscription,cafe_order',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $csvData = $this->billingService->exportBillingHistory(
            $cafe,
            $request->get('period', 'all_time'),
            $request->get('type')
        );

        // Generate CSV content
        $csvContent = $this->arrayToCsv($csvData);

        // Set headers for CSV download
        $filename = 'billing_history_' . now()->format('Y-m-d_His') . '.csv';

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * 9. PUT /api/v1/cafe-admin/billing/payment-method
     * Update default billing payment method
     */
    public function updatePaymentMethod(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        // Store payment method ID directly on cafe
        $cafe->update([
            'payment_method_id' => $request->payment_method_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default payment method updated successfully',
        ]);
    }

    /**
     * Convert array to CSV string
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
