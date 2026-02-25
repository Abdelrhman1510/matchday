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
        // branch_id is now in the main chat_rooms migration
        // Only add the unique index if needed
        if (!Schema::hasColumn('chat_rooms', 'branch_id')) {
            Schema::table('chat_rooms', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('match_id')->constrained()->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropUnique('chat_rooms_match_branch_type_unique');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
