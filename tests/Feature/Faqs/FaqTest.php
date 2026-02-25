<?php

namespace Tests\Feature\Faqs;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_active_faqs()
    {
        Faq::create([
            'question' => 'How do I book?',
            'answer' => 'Browse matches and select a seat.',
            'category' => 'Booking',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Faq::create([
            'question' => 'How do I cancel?',
            'answer' => 'Go to My Bookings and tap Cancel.',
            'category' => 'Cancellation',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        Faq::create([
            'question' => 'Hidden FAQ',
            'answer' => 'This should not appear.',
            'category' => 'General',
            'sort_order' => 3,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/faqs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'faqs' => [
                        '*' => ['id', 'question', 'answer', 'category', 'sort_order'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.faqs'));
    }

    /** @test */
    public function it_returns_faqs_ordered_by_sort_order()
    {
        Faq::create([
            'question' => 'Third FAQ',
            'answer' => 'Answer 3',
            'sort_order' => 3,
            'is_active' => true,
        ]);
        Faq::create([
            'question' => 'First FAQ',
            'answer' => 'Answer 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Faq::create([
            'question' => 'Second FAQ',
            'answer' => 'Answer 2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/faqs');

        $response->assertStatus(200);

        $faqs = $response->json('data.faqs');
        $this->assertEquals('First FAQ', $faqs[0]['question']);
        $this->assertEquals('Second FAQ', $faqs[1]['question']);
        $this->assertEquals('Third FAQ', $faqs[2]['question']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_active_faqs()
    {
        $response = $this->getJson('/api/v1/faqs');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'faqs' => [],
                ],
            ]);
    }

    /** @test */
    public function it_does_not_require_auth_for_faqs()
    {
        Faq::create([
            'question' => 'Public FAQ',
            'answer' => 'This is public.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/faqs');

        $response->assertStatus(200);
    }
}
