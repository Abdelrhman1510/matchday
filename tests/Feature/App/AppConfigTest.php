<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_app_version()
    {
        $response = $this->getJson('/api/v1/app/version');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['current_version', 'min_version', 'force_update'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'current_version' => '1.0.0',
                    'min_version' => '1.0.0',
                    'force_update' => false,
                ],
            ]);
    }

    /** @test */
    public function it_returns_app_config()
    {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'maintenance_mode',
                    'support_email',
                    'support_phone',
                    'terms_url',
                    'privacy_url',
                    'default_currency',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'maintenance_mode' => false,
                    'support_email' => 'support@matchday.app',
                    'support_phone' => '+966500000000',
                    'default_currency' => 'SAR',
                ],
            ]);
    }

    /** @test */
    public function it_does_not_require_auth_for_version()
    {
        $response = $this->getJson('/api/v1/app/version');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_does_not_require_auth_for_config()
    {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_includes_urls_in_config()
    {
        $response = $this->getJson('/api/v1/app/config');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'terms_url' => '/api/v1/pages/terms-conditions',
                    'privacy_url' => '/api/v1/pages/privacy-policy',
                ],
            ]);
    }
}
