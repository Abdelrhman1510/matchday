<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\DashboardPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_the_dashboard_without_error()
    {
        // Regression: the #[Lazy] dashboard loads its real content via a Livewire
        // update request that runs render(); a missing public property there threw
        // PropertyNotFoundException and 500'd right after platform-admin login.
        Livewire::test(DashboardPage::class)
            ->assertOk();
    }

    /** @test */
    public function it_filters_recent_matches_by_search_term()
    {
        Livewire::test(DashboardPage::class)
            ->set('searchMatches', 'anything')
            ->assertOk()
            ->assertSet('searchMatches', 'anything');
    }
}
