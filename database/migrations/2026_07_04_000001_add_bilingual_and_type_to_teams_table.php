<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('teams', 'type')) {
                $table->string('type', 20)->default('club')->after('league'); // club | national
            }
            if (!Schema::hasColumn('teams', 'wikidata_id')) {
                $table->string('wikidata_id', 20)->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            foreach (['name_ar', 'type', 'wikidata_id'] as $col) {
                if (Schema::hasColumn('teams', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
