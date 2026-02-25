<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\SeatingSection;
use App\Models\Seat;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeatingAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_seating_section_with_auto_labels()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/seating-sections", [
            'name' => 'Section A',
            'total_seats' => 20,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'seats'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Section A',
                ],
            ]);

        // Verify seats auto-labeled A1, A2, A3, etc.
        $this->assertDatabaseHas('seats', [
            'label' => 'A1',
        ]);
        $this->assertDatabaseHas('seats', [
            'label' => 'A20',
        ]);
    }

    /** @test */
    public function it_bulk_creates_seats()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/seating-sections/{$section->id}/seats/bulk", [
            'seats' => [
                ['label' => 'VIP1', 'price' => 100],
                ['label' => 'VIP2', 'price' => 100],
                ['label' => 'VIP3', 'price' => 100],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('seats', [
            'section_id' => $section->id,
            'label' => 'VIP1',
            'price' => 100,
        ]);
    }

    /** @test */
    public function it_updates_seat()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/seats/{$seat->id}", [
            'label' => 'Updated Label',
            'price' => 150,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'label' => 'Updated Label',
                    'price' => 150,
                ],
            ]);
    }

    /** @test */
    public function it_returns_422_for_deleting_section_with_active_bookings()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id]);
        
        // Create booking for this seat
        $booking = Booking::factory()->create(['status' => 'confirmed']);
        $booking->seats()->attach($seat->id);
        
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/seating-sections/{$section->id}");

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_deletes_section_without_bookings()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/seating-sections/{$section->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('seating_sections', [
            'id' => $section->id,
        ]);
    }

    /** @test */
    public function it_lists_branch_seating_layout()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        Seat::factory()->count(5)->create(['section_id' => $section->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/seating-layout");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'section_id',
                        'section_name',
                        'seats' => [
                            '*' => ['id', 'label', 'price', 'is_available'],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_toggles_seat_availability()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create([
            'section_id' => $section->id,
            'is_available' => true,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/seats/{$seat->id}/toggle-availability");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $seat->refresh();
        $this->assertFalse($seat->is_available);
    }

    /** @test */
    public function it_returns_403_for_fan_accessing_seating_admin()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        
        Sanctum::actingAs($fan);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/seating-sections", [
            'name' => 'Section A',
            'total_seats' => 10,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_seat_price()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/seats/{$seat->id}", [
            'price' => -10,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['price'],
            ]);
    }
}
