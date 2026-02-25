<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            if (!Schema::hasColumn('cafes', 'cafe_type')) {
                $table->string('cafe_type', 50)->nullable()->after('description');
            }
            if (!Schema::hasColumn('cafes', 'cancellation_hours')) {
                $table->integer('cancellation_hours')->default(2)->after('cafe_type');
            }
            if (!Schema::hasColumn('cafes', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('cancellation_hours');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'pitches_count')) {
                $table->integer('pitches_count')->default(0)->after('total_seats');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->dropColumn(['cafe_type', 'cancellation_hours', 'cancellation_policy']);
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'pitches_count')) {
                $table->dropColumn('pitches_count');
            }
        });
    }
};
