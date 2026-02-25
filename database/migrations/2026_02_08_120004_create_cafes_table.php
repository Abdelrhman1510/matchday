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
        Schema::create('cafes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('phone');
            $table->string('city')->index();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('avg_rating', 2, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->enum('subscription_plan', ['starter', 'pro', 'elite'])->default('starter');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cafes');
    }
};
