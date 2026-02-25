<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfferAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_offer()
    {
        Storage::fake('public');
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/offers", [
            'title' => '20% Off Match Day',
            'description' => 'Special discount for all matches',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'valid_from' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'discount_type', 'discount_value'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('offers', [
            'branch_id' => $branch->id,
            'title' => '20% Off Match Day',
            'discount_value' => 20,
        ]);
    }

    /** @test */
    public function it_uploads_offer_image()
    {
        Storage::fake('public');
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $offer = Offer::factory()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $file = UploadedFile::fake()->image('offer.jpg', 800, 600);

        $response = $this->postJson("/api/v1/admin/offers/{$offer->id}/image", [
            'image' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['image_url'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        Storage::disk('public')->assertExists('offers/' . $file->hashName());
    }

    /** @test */
    public function it_lists_branch_offers()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Offer::factory()->count(5)->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/offers");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'discount_type', 'is_active', 'valid_until'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_updates_offer()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $offer = Offer::factory()->create([
            'branch_id' => $branch->id,
            'title' => 'Old Title',
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/offers/{$offer->id}", [
            'title' => 'Updated Title',
            'discount_value' => 25,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['title' => 'Updated Title'],
            ]);

        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_toggles_offer_status()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $offer = Offer::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/offers/{$offer->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_deletes_offer()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $offer = Offer::factory()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/offers/{$offer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('offers', [
            'id' => $offer->id,
        ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_date_range()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/offers", [
            'title' => 'Invalid Offer',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'valid_from' => now()->addDays(10)->format('Y-m-d'),
            'valid_until' => now()->format('Y-m-d'), // Invalid: ends before it starts
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['valid_until'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_discount_value()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/offers", [
            'title' => 'Invalid Discount',
            'discount_type' => 'percentage',
            'discount_value' => 150, // Invalid: percentage > 100
            'valid_from' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['discount_value'],
            ]);
    }

    /** @test */
    public function it_returns_403_for_fan_creating_offer()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        Sanctum::actingAs($fan);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/offers", [
            'title' => 'Unauthorized Offer',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'valid_from' => now(),
            'valid_until' => now()->addDays(30),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_automatically_expires_past_offers()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        // Create expired offer
        $expiredOffer = Offer::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
            'valid_until' => now()->subDays(1),
        ]);
        
        Sanctum::actingAs($owner);

        // Simulate command to expire offers
        $this->artisan('offers:expire');

        $this->assertDatabaseHas('offers', [
            'id' => $expiredOffer->id,
            'is_active' => false,
        ]);
    }
}
