<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Plans created in the dashboard have their own slugs; expiry also clears this column. */
    public function up(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->string('subscription_plan', 100)->nullable()->default('starter')->change();
        });
    }

    public function down(): void
    {
        DB::table('cafes')
            ->where(fn ($q) => $q->whereNull('subscription_plan')->orWhereNotIn('subscription_plan', ['starter', 'pro', 'elite']))
            ->update(['subscription_plan' => 'starter']);

        Schema::table('cafes', function (Blueprint $table) {
            $table->enum('subscription_plan', ['starter', 'pro', 'elite'])->default('starter')->change();
        });
    }
};
