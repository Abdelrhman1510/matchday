<?php

namespace Tests\Feature\Home;

use App\Models\User;
use App\Models\Offer;
use App\Models\Cafe;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_active_offers()
    {
        Offer::factory()->count(3)->create([
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(7),
        ]);
        Offer::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'description', 'discount', 'cafe'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_single_offer_detail()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $offer = Offer::factory()->create([
            'branch_id' => $branch->id,
            'title' => 'Happy Hour Special',
            'discount' => 20,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/offers/{$offer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'discount',
                    'terms',
                    'start_date',
                    'end_date',
                    'cafe',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Happy Hour Special',
                    'discount' => 20,
                ],
            ]);
    }

    /** @test */
    public function it_filters_offers_by_featured()
    {
        Offer::factory()->count(2)->create([
            'status' => 'active',
            'is_featured' => true,
        ]);
        Offer::factory()->count(3)->create([
            'status' => 'active',
            'is_featured' => false,
        ]);

        $response = $this->getJson('/api/v1/offers?featured=true');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_filters_offers_by_cafe()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        Offer::factory()->count(3)->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        Offer::factory()->count(2)->create(['status' => 'active']);

        $response = $this->getJson("/api/v1/offers?cafe_id={$cafe->id}");

        $response->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_offer()
    {
        $response = $this->getJson('/api/v1/offers/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_does_not_list_expired_offers()
    {
        Offer::factory()->count(2)->create([
            'status' => 'active',
            'end_date' => now()->subDay(),
        ]);
        Offer::factory()->count(3)->create([
            'status' => 'active',
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/v1/offers');

        $response->assertStatus(200);

        // Should only return non-expired offers
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_lists_offers_without_authentication()
    {
        Offer::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/offers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_lists_offers_with_authentication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        Offer::factory()->count(3)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/offers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_searches_offers_by_title()
    {
        Offer::factory()->create([
            'title' => 'Weekend Special',
            'status' => 'active',
        ]);
        Offer::factory()->create([
            'title' => 'Happy Hour',
            'status' => 'active',
        ]);
        Offer::factory()->create([
            'title' => 'Weekend Discount',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/offers?search=Weekend');

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_paginates_offers()
    {
        Offer::factory()->count(25)->create(['status' => 'active']);

        $response = $this->getJson('/api/v1/offers?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data.data'));
    }
}
