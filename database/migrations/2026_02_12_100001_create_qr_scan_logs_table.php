<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->foreignId('scanned_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('booking_code', 50)->nullable();
            $table->enum('result', ['success', 'not_found', 'wrong_cafe', 'already_checked_in', 'invalid_status', 'error']);
            $table->string('error_message')->nullable();
            $table->unsignedInteger('processing_ms')->default(0);
            $table->timestamps();

            $table->index(['cafe_id', 'created_at']);
            $table->index(['scanned_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_scan_logs');
    }
};
