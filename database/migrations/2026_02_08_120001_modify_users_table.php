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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('role', ['fan', 'cafe_owner', 'staff'])->default('fan')->after('avatar');
            $table->string('google_id')->unique()->nullable()->after('role');
            $table->string('apple_id')->unique()->nullable()->after('google_id');
            $table->enum('locale', ['en', 'ar'])->default('en')->after('apple_id');
            $table->string('device_token')->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('device_token');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar',
                'role',
                'google_id',
                'apple_id',
                'locale',
                'device_token',
                'is_active',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
