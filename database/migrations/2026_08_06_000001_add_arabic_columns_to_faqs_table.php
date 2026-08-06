<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BUG-084: FAQ section remains in English regardless of selected app language.
     *
     * Add Arabic-language columns for question and answer so the API can return
     * the correct content based on the caller's locale. Existing English values
     * are preserved; Arabic fields default to NULL until filled by the platform admin.
     */
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            // Rename existing columns to make the locale explicit, then add Arabic twins.
            // To avoid a breaking rename we add new nullable Arabic columns alongside
            // the existing English ones.
            $table->string('question_ar', 500)->nullable()->after('question');
            $table->text('answer_ar')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['question_ar', 'answer_ar']);
        });
    }
};
