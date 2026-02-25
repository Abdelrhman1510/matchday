<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BillingService
{
    /**
     * Get transaction history with filters
     */
    public function getTransactionHistory(
        Cafe $cafe,
        ?string $period = 'all_time',
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Payment::query()
            ->where('user_id', $cafe->owner_id)
            ->with(['booking', 'paymentMethod']);

        // Apply period filter
        $query = $this->applyPeriodFilter($query, $period);

        // Apply type filter
        if ($type && in_array($type, ['booking', 'subscription', 'cafe_order'])) {
            $query->where('type', $type);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get billing summary
     */
    public function getBillingSummary(Cafe $cafe): array
    {
        $cacheKey = "billing_summary_{$cafe->id}_" . now()->format('Y-m');

        return Cache::remember($cacheKey, 900, function () use ($cafe) {
            $thisMonthStart = now()->startOfMonth();
            $thisMonthEnd = now()->endOfMonth();

            $totalSpentThisMonth = Payment::where('user_id', $cafe->owner_id)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
                ->sum('amount');

            $totalTransactions = Payment::where('user_id', $cafe->owner_id)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
                ->count();

            return [
                'total_spent_this_month' => (float) $totalSpentThisMonth,
                'total_transactions' => $totalTransactions,
                'currency' => 'SAR',
            ];
        });
    }

    /**
     * Export billing history as CSV
     */
    public function exportBillingHistory(
        Cafe $cafe,
        ?string $period = 'all_time',
        ?string $type = null
    ): array {
        $query = Payment::query()
            ->where('user_id', $cafe->owner_id)
            ->with(['booking', 'paymentMethod']);

        $query = $this->applyPeriodFilter($query, $period);

        if ($type && in_array($type, ['booking', 'subscription', 'cafe_order'])) {
            $query->where('type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Prepare CSV headers
        $csvData = [
            ['ID', 'Title', 'Subtitle', 'Type', 'Status', 'Amount', 'Currency', 'Date', 'Time', 'Gateway Reference']
        ];

        // Add transaction rows
        foreach ($transactions as $transaction) {
            $formattedTransaction = $this->formatTransaction($transaction);
            
            $csvData[] = [
                $formattedTransaction['id'],
                $formattedTransaction['title'],
                $formattedTransaction['subtitle'],
                $formattedTransaction['type'],
                $formattedTransaction['status'],
                $formattedTransaction['amount'],
                $formattedTransaction['currency'],
                $formattedTransaction['date'],
                $formattedTransaction['time'],
                $transaction->gateway_ref ?? '',
            ];
        }

        return $csvData;
    }

    /**
     * Update default payment method
     */
    public function updatePaymentMethod(Cafe $cafe, int $paymentMethodId): bool
    {
        $paymentMethod = \App\Models\PaymentMethod::where('id', $paymentMethodId)
            ->where('user_id', $cafe->owner_id)
            ->first();

        if (!$paymentMethod) {
            return false;
        }

        // Set all payment methods to non-primary
        \App\Models\PaymentMethod::where('user_id', $cafe->owner_id)
            ->update(['is_primary' => false]);

        // Set this one as primary
        $paymentMethod->update(['is_primary' => true]);

        // Update active subscription's payment method
        $activeSubscription = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($activeSubscription) {
            $activeSubscription->update([
                'payment_method_id' => $paymentMethodId,
            ]);
        }

        return true;
    }

    /**
     * Format transaction for API response
     */
    public function formatTransaction(Payment $payment): array
    {
        $title = $this->getTransactionTitle($payment);
        $subtitle = $this->getTransactionSubtitle($payment);

        return [
            'id' => $payment->id,
            'title' => $title,
            'subtitle' => $subtitle,
            'type' => $payment->type,
            'status' => strtoupper($payment->status),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'date' => $payment->created_at->format('Y-m-d'),
            'time' => $payment->created_at->format('H:i:s'),
            'gateway_ref' => $payment->gateway_ref,
        ];
    }

    /**
     * Apply period filter to query
     */
    private function applyPeriodFilter(Builder $query, ?string $period): Builder
    {
        switch ($period) {
            case 'this_month':
                $query->whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'last_3_months':
                $query->whereBetween('created_at', [
                    now()->subMonths(3)->startOfDay(),
                    now()->endOfDay()
                ]);
                break;
            case 'all_time':
            default:
                // No filter
                break;
        }

        return $query;
    }

    /**
     * Get transaction title based on type
     */
    private function getTransactionTitle(Payment $payment): string
    {
        switch ($payment->type) {
            case 'subscription':
                return 'Subscription Payment';
            case 'booking':
                return 'Booking Payment';
            case 'cafe_order':
                return 'Cafe Order Payment';
            default:
                return 'Payment';
        }
    }

    /**
     * Get transaction subtitle with details
     */
    private function getTransactionSubtitle(Payment $payment): string
    {
        switch ($payment->type) {
            case 'subscription':
                return $payment->description ?? 'Subscription renewal';
            case 'booking':
                if ($payment->booking) {
                    $matchInfo = $payment->booking->match 
                        ? $payment->booking->match->home_team . ' vs ' . $payment->booking->match->away_team 
                        : 'Match booking';
                    return $matchInfo . ' - ' . $payment->booking->total_guests . ' guests';
                }
                return 'Booking payment';
            case 'cafe_order':
                return $payment->description ?? 'Cafe order';
            default:
                return $payment->description ?? '';
        }
    }
}
