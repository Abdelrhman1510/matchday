<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('subscription_plans', 'features_ar')) {
                $table->json('features_ar')->nullable()->after('features');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            foreach (['name_ar', 'features_ar'] as $column) {
                if (Schema::hasColumn('subscription_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
