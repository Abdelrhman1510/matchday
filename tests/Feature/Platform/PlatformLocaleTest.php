<?php

namespace Tests\Feature\Platform;

use App\Services\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformLocaleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function web_requests_follow_platform_language_not_the_browser_accept_language()
    {
        // The platform is configured for Arabic...
        app(PlatformSettingsService::class)->set('platform_language', 'ar', 'string', 'language');

        // ...but the tester's browser advertises English. Previously SetLocale
        // let Accept-Language override the platform setting, and because
        // Livewire's XHR requests resolve that header differently than the
        // top-level navigation, the sidebar (GET) and the lazy-loaded content
        // (POST) rendered in different languages.
        $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
            ->get('/platform/login')
            ->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }

    /** @test */
    public function api_requests_still_honor_accept_language()
    {
        // The mobile/public API has no platform setting to honor, so the
        // Accept-Language fallback must keep working there.
        $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/v1/teams')
            ->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }
}
