<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The cafe a subscription charge is for, so a charge completed later via the
     * webhook applies its plan to exactly that cafe (owners may have several).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('cafe_id')->nullable()->after('plan_id')
                ->constrained('cafes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cafe_id');
        });
    }
};
