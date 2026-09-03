<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // Card network (visa, mastercard, mada, …) — read authoritatively from
            // the Moyasar token, returned in payment-method responses.
            $table->string('card_brand', 40)->nullable()->after('card_last_four');
            // The Moyasar payment id used to verify/tokenize this card. Stored so a
            // single SAR 1 authorization can never be replayed to attach a card twice.
            $table->string('provider_payment_id')->nullable()->unique()->after('provider_token');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropUnique(['provider_payment_id']);
            $table->dropColumn(['card_brand', 'provider_payment_id']);
        });
    }
};
