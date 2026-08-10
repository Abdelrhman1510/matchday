<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Arabic translation column for the FAQ category field so that
     * Arabic-locale callers receive a fully localised response including
     * the category name, not just the question and answer.
     */
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('category_ar')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('category_ar');
        });
    }
};
