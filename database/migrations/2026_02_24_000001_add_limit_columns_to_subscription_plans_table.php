<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Limit columns (null = unlimited)
            $table->integer('max_branches')->nullable()->after('has_priority_support');
            $table->integer('max_matches_per_month')->nullable()->after('max_branches');
            $table->integer('max_bookings_per_month')->nullable()->after('max_matches_per_month');
            $table->integer('max_staff_members')->nullable()->after('max_bookings_per_month');
            $table->integer('max_offers')->nullable()->after('max_staff_members');

            // Feature flags
            $table->boolean('has_chat')->default(false)->after('max_offers');
            $table->boolean('has_qr_scanner')->default(false)->after('has_chat');
            $table->boolean('has_occupancy_tracking')->default(false)->after('has_qr_scanner');

            // Commission rate override (null = use platform default)
            $table->decimal('commission_rate', 5, 2)->nullable()->after('has_occupancy_tracking');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'max_branches',
                'max_matches_per_month',
                'max_bookings_per_month',
                'max_staff_members',
                'max_offers',
                'has_chat',
                'has_qr_scanner',
                'has_occupancy_tracking',
                'commission_rate',
            ]);
        });
    }
};
