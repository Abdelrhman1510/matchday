<?php

namespace Tests\Feature\Platform;

use App\Livewire\Platform\SettingsPage;
use App\Models\Cafe;
use App\Services\PlatformSettingsService;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnlimitedModeTest extends TestCase
{
    use RefreshDatabase;

    private function enforcement(): SubscriptionEnforcementService
    {
        return app(SubscriptionEnforcementService::class);
    }

    private function setUnlimited(bool $on): void
    {
        app(PlatformSettingsService::class)->set('unlimited_mode', $on, 'bool', 'plans');
    }

    /** @test */
    public function without_unlimited_mode_a_cafe_with_no_subscription_is_blocked()
    {
        $cafe = Cafe::factory()->create();

        $this->assertFalse($this->enforcement()->canReceiveBooking($cafe)['allowed']);
        $this->assertFalse($this->enforcement()->hasActiveSubscription($cafe));
        $this->assertFalse($this->enforcement()->hasFeature($cafe, 'has_analytics'));
    }

    /** @test */
    public function unlimited_mode_lifts_all_limits_and_features_for_everyone()
    {
        $cafe = Cafe::factory()->create(); // no subscription at all
        $this->setUnlimited(true);

        $this->assertTrue($this->enforcement()->canReceiveBooking($cafe)['allowed']);
        $this->assertTrue($this->enforcement()->canCreateBranch($cafe)['allowed']);
        $this->assertTrue($this->enforcement()->canCreateMatch($cafe)['allowed']);
        $this->assertTrue($this->enforcement()->canAddStaff($cafe)['allowed']);
        $this->assertTrue($this->enforcement()->canCreateOffer($cafe)['allowed']);
        $this->assertTrue($this->enforcement()->hasActiveSubscription($cafe));
        $this->assertTrue($this->enforcement()->hasFeature($cafe, 'has_qr_scanner'));

        $summary = $this->enforcement()->getUsageSummary($cafe);
        $this->assertTrue($summary['unlimited_mode']);
        $this->assertTrue($summary['usage']['bookings_this_month']['unlimited']);
        $this->assertNull($summary['usage']['bookings_this_month']['limit']);
    }

    /** @test */
    public function toggling_it_back_off_restores_enforcement()
    {
        $cafe = Cafe::factory()->create();
        $this->setUnlimited(true);
        $this->assertTrue($this->enforcement()->canReceiveBooking($cafe)['allowed']);

        $this->setUnlimited(false);
        $this->assertFalse($this->enforcement()->canReceiveBooking($cafe)['allowed']);
    }

    /** @test */
    public function the_settings_toggle_persists_the_platform_setting()
    {
        Livewire::test(SettingsPage::class)
            ->set('unlimited_mode', true)
            ->assertSet('unlimited_mode', true);

        $this->assertTrue((bool) app(PlatformSettingsService::class)->get('unlimited_mode', false));
    }
}
