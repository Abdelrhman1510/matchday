<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LoyaltyCard;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns without constraints first
        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->string('card_number')->nullable()->after('user_id');
            $table->timestamp('issued_date')->nullable()->after('total_points_earned');
        });
        
        // Generate card numbers for existing records
        $existingCards = LoyaltyCard::whereNull('card_number')->get();
        foreach ($existingCards as $card) {
            $card->card_number = 'MD' . date('Y') . str_pad($card->id, 7, '0', STR_PAD_LEFT);
            $card->issued_date = $card->created_at ?? now();
            $card->save();
        }
        
        // Now add the unique constraint
        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->string('card_number')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'issued_date']);
        });
    }
};
