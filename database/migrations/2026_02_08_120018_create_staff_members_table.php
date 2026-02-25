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
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'manager', 'staff'])->default('staff');
            $table->boolean('can_manage_bookings')->default(false);
            $table->boolean('can_view_analytics')->default(false);
            $table->boolean('can_manage_menu')->default(false);
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('invitation_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cafe_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
