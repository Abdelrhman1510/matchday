<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\CafeSubscription;
use App\Models\Cafe;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Starter Plan
        $starter = SubscriptionPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'price' => 49.00,
                'currency' => 'SAR',
                'features' => [
                    'Up to 50 bookings per month',
                    'Basic analytics dashboard',
                    'Email support',
                    '1 branch location',
                    'Standard branding',
                ],
                'max_bookings' => 50,
                'has_analytics' => true,
                'has_branding' => false,
                'has_priority_support' => false,
                'is_active' => true,
                // New limit columns
                'max_branches' => 1,
                'max_matches_per_month' => 10,
                'max_bookings_per_month' => 50,
                'max_staff_members' => 3,
                'max_offers' => 2,
                'has_chat' => false,
                'has_qr_scanner' => false,
                'has_occupancy_tracking' => false,
                'commission_rate' => null,
            ]
        );

        // Pro Plan
        $pro = SubscriptionPlan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'price' => 99.00,
                'currency' => 'SAR',
                'features' => [
                    'Up to 200 bookings per month',
                    'Advanced analytics & insights',
                    'Priority email & chat support',
                    'Up to 3 branch locations',
                    'Custom branding options',
                    'Featured in search results',
                    'Promotional offers system',
                ],
                'max_bookings' => 200,
                'has_analytics' => true,
                'has_branding' => true,
                'has_priority_support' => false,
                'is_active' => true,
                // New limit columns
                'max_branches' => 3,
                'max_matches_per_month' => 50,
                'max_bookings_per_month' => 200,
                'max_staff_members' => 10,
                'max_offers' => 10,
                'has_chat' => true,
                'has_qr_scanner' => true,
                'has_occupancy_tracking' => false,
                'commission_rate' => null,
            ]
        );

        // Elite Plan
        $elite = SubscriptionPlan::updateOrCreate(
            ['slug' => 'elite'],
            [
                'name' => 'Elite',
                'price' => 199.00,
                'currency' => 'SAR',
                'features' => [
                    'Unlimited bookings',
                    'Premium analytics with AI insights',
                    '24/7 Priority support',
                    'Unlimited branch locations',
                    'Full custom branding',
                    'Top placement in search',
                    'Advanced promotional tools',
                    'Dedicated account manager',
                    'API access',
                    'Early access to new features',
                ],
                'max_bookings' => null,
                'has_analytics' => true,
                'has_branding' => true,
                'has_priority_support' => true,
                'is_active' => true,
                // New limit columns (null = unlimited)
                'max_branches' => null,
                'max_matches_per_month' => null,
                'max_bookings_per_month' => null,
                'max_staff_members' => null,
                'max_offers' => null,
                'has_chat' => true,
                'has_qr_scanner' => true,
                'has_occupancy_tracking' => true,
                'commission_rate' => null,
            ]
        );

        $this->command->info('Subscription plans seeded successfully!');
        $this->command->info('Starter: 49 SAR, Pro: 99 SAR, Elite: 199 SAR');
    }
}
