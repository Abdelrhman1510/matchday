<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The seat API has always accepted labels up to 50 characters. */
    public function up(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->string('label', 50)->change();
        });
    }

    public function down(): void
    {
        DB::table('seats')->update(['label' => DB::raw('SUBSTR(label, 1, 10)')]);

        Schema::table('seats', function (Blueprint $table) {
            $table->string('label', 10)->change();
        });
    }
};
