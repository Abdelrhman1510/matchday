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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('home_team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('away_team_id')->constrained('teams')->onDelete('cascade');
            $table->string('league', 100)->nullable()->default('TBD');
            $table->date('match_date')->index();
            $table->time('kick_off')->nullable();
            $table->tinyInteger('home_score')->nullable();
            $table->tinyInteger('away_score')->nullable();
            $table->string('status')->default('upcoming')->index();
            $table->integer('seats_available')->default(0);
            $table->decimal('price_per_seat', 8, 2)->default(0);
            $table->integer('duration_minutes')->default(90);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->timestamp('booking_opens_at')->nullable();
            $table->timestamp('booking_closes_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
