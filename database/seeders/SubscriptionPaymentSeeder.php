<?php

namespace Database\Seeders;

use App\Models\CafeSubscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionPaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Get all active subscriptions with cafe owner
        $subscriptions = CafeSubscription::with('cafe.owner')->where('status', 'active')->get();
        
        // Backfill payments for last 6 months
        $monthsToBackfill = 6;
        
        foreach ($subscriptions as $subscription) {
            // Get cafe owner user_id
            $cafeOwnerId = $subscription->cafe->owner->id ?? null;
            
            if (!$cafeOwnerId) {
                echo "Skipping subscription {$subscription->id} - no cafe owner found\n";
                continue;
            }
            
            // Create historical payment records for the last 6 months
            for ($i = $monthsToBackfill - 1; $i >= 0; $i--) {
                $paymentDate = Carbon::now()->subMonths($i);
                
                Payment::create([
                    'booking_id' => null,
                    'user_id' => $cafeOwnerId,
                    'payment_method_id' => null,
                    'amount' => $subscription->plan->price,
                    'currency' => 'SAR',
                    'status' => 'completed',
                    'type' => 'subscription',
                    'description' => 'Subscription: ' . $subscription->plan->name . ' - ' . $paymentDate->format('M Y'),
                    'gateway_ref' => 'sub_' . $subscription->id . '_' . $paymentDate->format('Ym') . '_' . uniqid(),
                    'paid_at' => $paymentDate,
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]);
            }
        }
        
        $totalPayments = Payment::where('type', 'subscription')->count();
        $totalRevenue = Payment::where('type', 'subscription')->sum('amount');
        
        echo "Created {$totalPayments} subscription payment records\n";
        echo "Total subscription revenue: \${$totalRevenue}\n";
    }
}
