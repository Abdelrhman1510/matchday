<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\CafeDetailPage;
use App\Livewire\Platform\MatchesPage;
use App\Livewire\Platform\PlanManagementPage;
use App\Models\Branch;
use App\Models\SubscriptionPlan;
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

    /** @test */
    public function matches_view_all_uses_a_real_method_not_the_missing_toggle_magic_action()
    {
        // The blade's wire:click="toggleShowAll" 500'd when it was $toggle(),
        // a magic action this Livewire version doesn't provide. The method must
        // exist and flip the flag. (Rendering MatchesPage under SQLite isn't
        // possible — its analytics query uses MySQL's HOUR() — so exercise the
        // action directly.)
        $component = new MatchesPage();
        $this->assertTrue(method_exists($component, 'toggleShowAll'));

        $this->assertFalse($component->showAll);
        $component->toggleShowAll();
        $this->assertTrue($component->showAll);
        $component->toggleShowAll();
        $this->assertFalse($component->showAll);
    }

    /** @test */
    public function editing_a_plan_with_a_null_arabic_name_opens_without_error()
    {
        // Regression: name_ar is nullable but $formNameAr is a non-nullable
        // string property, so editing such a plan threw a TypeError (500).
        $plan = SubscriptionPlan::factory()->create(['name_ar' => null]);

        Livewire::test(PlanManagementPage::class)
            ->call('openEditModal', $plan->id)
            ->assertSet('showModal', true)
            ->assertSet('formNameAr', '')
            ->assertOk();
    }
}
