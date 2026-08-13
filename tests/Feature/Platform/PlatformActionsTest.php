<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\CafeDetailPage;
use App\Models\Branch;
use App\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cafe_detail_pdf_export_returns_a_download()
    {
        $cafe = Cafe::factory()->create();
        Branch::factory()->create(['cafe_id' => $cafe->id]);

        Livewire::test(CafeDetailPage::class, ['cafe' => $cafe])
            ->call('exportToPDF')
            ->assertFileDownloaded('cafe-analytics-' . $cafe->id . '.pdf');
    }

    /** @test */
    public function cafe_detail_csv_export_returns_a_download()
    {
        $cafe = Cafe::factory()->create();
        Branch::factory()->create(['cafe_id' => $cafe->id]);

        Livewire::test(CafeDetailPage::class, ['cafe' => $cafe])
            ->call('exportToCSV')
            ->assertFileDownloaded('cafe-bookings-' . $cafe->id . '.csv');
    }
}
