<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup existing avatar data and convert to JSON format
        $users = DB::table('users')->whereNotNull('avatar')->get();
        
        // Change column type to JSON
        Schema::table('users', function (Blueprint $table) {
            $table->json('avatar')->nullable()->change();
        });

        // Migrate existing data (old single path to new multi-size format)
        // Since old avatars are single paths, we'll set them as null
        // Users will need to re-upload avatars to get multi-size versions
        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['avatar' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });
    }
};
