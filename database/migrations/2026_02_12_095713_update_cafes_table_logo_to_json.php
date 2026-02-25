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
        // First, convert existing string logo paths to JSON format
        $cafes = DB::table('cafes')->whereNotNull('logo')->get();
        
        foreach ($cafes as $cafe) {
            // If it's not already JSON (no braces), convert it
            if (!empty($cafe->logo) && !str_starts_with(trim($cafe->logo), '{') && !str_starts_with(trim($cafe->logo), '[')) {
                $logoArray = [
                    'original' => $cafe->logo,
                    'medium' => $cafe->logo,
                    'thumbnail' => $cafe->logo,
                ];
                
                DB::table('cafes')
                    ->where('id', $cafe->id)
                    ->update(['logo' => json_encode($logoArray)]);
            }
        }
        
        // Now alter the column type to JSON
        Schema::table('cafes', function (Blueprint $table) {
            $table->json('logo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cafes', function (Blueprint $table) {
            $table->string('logo')->nullable()->change();
        });
    }
};
