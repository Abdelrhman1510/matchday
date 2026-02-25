<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2. Add missing columns to game_matches
        Schema::table('game_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('game_matches', 'is_live')) {
                $table->boolean('is_live')->default(false)->after('is_published');
            }
            if (!Schema::hasColumn('game_matches', 'ticket_price')) {
                $table->decimal('ticket_price', 10, 2)->nullable()->after('price_per_seat');
            }
        });

        // 3. Add missing columns to branches
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
            if (!Schema::hasColumn('branches', 'city')) {
                $table->string('city')->nullable()->after('phone');
            }
        });

        // 4. Add price to seats
        Schema::table('seats', function (Blueprint $table) {
            if (!Schema::hasColumn('seats', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->default(0)->after('label');
            }
        });

        // 5. Add subscription_id to payments
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable()->after('booking_id');
            }
        });

        // 6. Add scheduled_plan_id to cafe_subscriptions
        Schema::table('cafe_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('cafe_subscriptions', 'scheduled_plan_id')) {
                $table->unsignedBigInteger('scheduled_plan_id')->nullable()->after('plan_id');
            }
        });

        // 7. Add payment_method_id to cafes (string type for Stripe IDs like 'pm_card_visa')
        Schema::table('cafes', function (Blueprint $table) {
            if (!Schema::hasColumn('cafes', 'payment_method_id')) {
                $table->string('payment_method_id')->nullable()->after('subscription_plan');
            }
        });

        // 8. Add softDeletes to seating_sections
        Schema::table('seating_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('seating_sections', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 9. Create amenities table
        if (!Schema::hasTable('amenities')) {
            Schema::create('amenities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('icon')->nullable();
                $table->timestamps();
            });
        }

        // 10. Create branch_amenity pivot table
        if (!Schema::hasTable('branch_amenity')) {
            Schema::create('branch_amenity', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('amenity_id');
                $table->timestamps();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
                $table->foreign('amenity_id')->references('id')->on('amenities')->onDelete('cascade');
                $table->unique(['branch_id', 'amenity_id']);
            });
        }

        // 11. Change cafe_subscriptions.status from enum to string to allow 'past_due'
        // SQLite doesn't enforce enum constraints, so this is handled in model
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_amenity');
        Schema::dropIfExists('amenities');
    }
};
