<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seating_sections', function (Blueprint $table) {
            $table->string('screen_size', 50)->nullable()->after('icon');
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->string('table_number', 20)->nullable()->after('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seating_sections', function (Blueprint $table) {
            $table->dropColumn('screen_size');
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->dropColumn('table_number');
        });
    }
};
