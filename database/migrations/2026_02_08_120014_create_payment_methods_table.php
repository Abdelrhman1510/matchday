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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit_card', 'debit_card', 'wallet', 'bank_transfer']);
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_holder')->nullable();
            $table->string('expires_at', 7)->nullable(); // YYYY-MM format
            $table->boolean('is_primary')->default(false);
            $table->text('provider_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
