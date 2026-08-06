<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Bug84Test extends TestCase
{
    use RefreshDatabase;

    private function makeFaq(): Faq
    {
        return Faq::create([
            'question'    => 'How do I book?',
            'question_ar' => 'كيف أحجز؟',
            'answer'      => 'Open the app and select a branch.',
            'answer_ar'   => 'افتح التطبيق واختر الفرع.',
            'category'    => 'general',
            'sort_order'  => 1,
            'is_active'   => true,
        ]);
    }

    #[Test]
    public function faq_returns_english_by_default(): void
    {
        $this->makeFaq();

        $response = $this->getJson('/api/v1/faqs');

        $response->assertStatus(200);
        $response->assertJsonPath('data.faqs.0.question', 'How do I book?');
        $response->assertJsonPath('data.faqs.0.answer', 'Open the app and select a branch.');
    }

    #[Test]
    public function faq_returns_arabic_when_accept_language_is_ar(): void
    {
        $this->makeFaq();

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/v1/faqs');

        $response->assertStatus(200);
        $response->assertJsonPath('data.faqs.0.question', 'كيف أحجز؟');
        $response->assertJsonPath('data.faqs.0.answer', 'افتح التطبيق واختر الفرع.');
    }

    #[Test]
    public function faq_falls_back_to_english_when_arabic_translation_missing(): void
    {
        Faq::create([
            'question'    => 'What is the cancellation policy?',
            'question_ar' => null,
            'answer'      => 'You may cancel up to 2 hours before.',
            'answer_ar'   => null,
            'sort_order'  => 2,
            'is_active'   => true,
        ]);

        $response = $this->withHeaders(['Accept-Language' => 'ar'])
            ->getJson('/api/v1/faqs');

        $response->assertStatus(200);
        // Should fall back to English when Arabic translation is absent
        $response->assertJsonPath('data.faqs.0.question', 'What is the cancellation policy?');
        $response->assertJsonPath('data.faqs.0.answer', 'You may cancel up to 2 hours before.');
    }
}
