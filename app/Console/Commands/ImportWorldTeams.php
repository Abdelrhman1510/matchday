<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Imports the world teams dataset (national teams + clubs, EN/AR names + logos)
 * from database/data/world_teams.json.
 *
 * Idempotent: upserts by wikidata_id, so re-running updates in place.
 *   php artisan teams:import           # upsert (safe to re-run)
 *   php artisan teams:import --fresh   # wipe teams first, then load
 */
class ImportWorldTeams extends Command
{
    protected $signature = 'teams:import {--fresh : Truncate the teams table before importing}';
    protected $description = 'Import national teams + world clubs (bilingual + logos) from the bundled dataset';

    public function handle(): int
    {
        $path = database_path('data/world_teams.json');
        if (!is_file($path)) {
            $this->error("Dataset not found: {$path}");
            return self::FAILURE;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (!is_array($rows) || empty($rows)) {
            $this->error('Dataset is empty or invalid JSON.');
            return self::FAILURE;
        }
        $this->info('Loaded ' . count($rows) . ' teams from dataset.');

        if ($this->option('fresh')) {
            $this->warn('--fresh: truncating teams table...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('teams')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $now = now();
        $cols = ['wikidata_id', 'name', 'name_ar', 'short_name', 'logo', 'league', 'type', 'country', 'is_popular', 'sort_order'];

        $bar = $this->output->createProgressBar(count($rows));
        foreach (array_chunk($rows, 500) as $chunk) {
            $batch = array_map(function ($r) use ($cols, $now) {
                $row = [];
                foreach ($cols as $c) {
                    $row[$c] = $r[$c] ?? null;
                }
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                return $row;
            }, $chunk);

            // Upsert by wikidata_id; update everything except timestamps/created_at.
            Team::upsert(
                $batch,
                ['wikidata_id'],
                ['name', 'name_ar', 'short_name', 'logo', 'league', 'type', 'country', 'is_popular', 'updated_at']
            );
            $bar->advance(count($chunk));
        }
        $bar->finish();
        $this->newLine(2);

        $this->info('Done. teams=' . Team::count()
            . ' | national=' . Team::where('type', 'national')->count()
            . ' | clubs=' . Team::where('type', 'club')->count());

        return self::SUCCESS;
    }
}
