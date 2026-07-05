<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('achievements', 'description_ar')) {
                $table->text('description_ar')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            foreach (['name_ar', 'description_ar'] as $col) {
                if (Schema::hasColumn('achievements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
