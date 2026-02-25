<?php

namespace Tests\Feature\Pages;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_page_by_slug()
    {
        Page::create([
            'slug' => 'privacy-policy',
            'title' => 'Privacy Policy',
            'content' => '<h1>Privacy Policy</h1><p>Content here.</p>',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/pages/privacy-policy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'page' => ['id', 'slug', 'title', 'content', 'updated_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'page' => [
                        'slug' => 'privacy-policy',
                        'title' => 'Privacy Policy',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_terms_and_conditions_page()
    {
        Page::create([
            'slug' => 'terms-conditions',
            'title' => 'Terms & Conditions',
            'content' => '<h1>Terms</h1><p>Terms content.</p>',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/pages/terms-conditions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'page' => [
                        'slug' => 'terms-conditions',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_slug()
    {
        $response = $this->getJson('/api/v1/pages/nonexistent-page');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Page not found.',
            ]);
    }

    /** @test */
    public function it_returns_404_for_inactive_page()
    {
        Page::create([
            'slug' => 'hidden-page',
            'title' => 'Hidden Page',
            'content' => 'This is hidden.',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/pages/hidden-page');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_does_not_require_auth_for_pages()
    {
        Page::create([
            'slug' => 'cookie-policy',
            'title' => 'Cookie Policy',
            'content' => '<p>Cookie policy content</p>',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/pages/cookie-policy');

        $response->assertStatus(200);
    }
}
