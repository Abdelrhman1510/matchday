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
        Schema::table('game_matches', function (Blueprint $table) {
            $table->string('field_name', 100)->nullable()->after('is_published');
            $table->string('venue_name', 255)->nullable()->after('field_name');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('venue_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropColumn(['field_name', 'venue_name', 'last_reminder_sent_at']);
        });
    }
};
