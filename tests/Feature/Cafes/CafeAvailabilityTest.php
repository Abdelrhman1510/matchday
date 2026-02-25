<?php

namespace Tests\Feature\Cafes;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function branch_shows_available_status_when_open_and_low_occupancy()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => true,
            'total_seats' => 100,
        ]);

        // No bookings = 0% occupancy → available
        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_status' => 'available',
                'status_color' => 'green',
            ]);
    }

    /** @test */
    public function branch_shows_closed_status_when_not_open()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => false,
            'total_seats' => 100,
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_status' => 'closed',
                'status_color' => 'gray',
            ]);
    }

    /** @test */
    public function branch_shows_busy_status_when_70_to_90_percent_occupied()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => true,
            'total_seats' => 100,
        ]);

        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->toDateString(),
            'status' => 'live',
            'is_published' => true,
        ]);

        // Create checked_in bookings totaling 80 guests (80% occupancy)
        Booking::factory()->checked_in()->create([
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'guests_count' => 80,
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_status' => 'busy',
                'status_color' => 'orange',
            ]);
    }

    /** @test */
    public function branch_shows_full_status_when_over_90_percent_occupied()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => true,
            'total_seats' => 100,
        ]);

        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->toDateString(),
            'status' => 'live',
            'is_published' => true,
        ]);

        // Create checked_in bookings totaling 95 guests (95% occupancy)
        Booking::factory()->checked_in()->create([
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'guests_count' => 95,
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_status' => 'full',
                'status_color' => 'red',
            ]);
    }

    /** @test */
    public function branch_model_computes_occupancy_percentage_correctly()
    {
        $branch = Branch::factory()->create([
            'is_open' => true,
            'total_seats' => 50,
        ]);

        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->toDateString(),
            'status' => 'live',
        ]);

        Booking::factory()->checked_in()->create([
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'guests_count' => 25,
        ]);

        $this->assertEquals(50.0, $branch->occupancy_percentage);
    }

    /** @test */
    public function branch_ignores_non_checked_in_bookings_for_occupancy()
    {
        $branch = Branch::factory()->create([
            'is_open' => true,
            'total_seats' => 100,
        ]);

        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->toDateString(),
            'status' => 'live',
        ]);

        // Confirmed but not checked in → should NOT count
        Booking::factory()->confirmed()->create([
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'guests_count' => 80,
        ]);

        $this->assertEquals(0.0, $branch->occupancy_percentage);
        $this->assertEquals('available', $branch->current_status);
    }

    /** @test */
    public function cafe_resource_includes_aggregate_availability_status()
    {
        $cafe = Cafe::factory()->create();
        Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => true,
            'total_seats' => 100,
        ]);

        // Use the list endpoint which uses CafeResource (branches eager-loaded)
        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200);
        $cafeData = collect($response->json('data.data'))->firstWhere('id', $cafe->id);
        $this->assertArrayHasKey('current_status', $cafeData);
        $this->assertArrayHasKey('status_color', $cafeData);
    }

    /** @test */
    public function cafe_shows_available_if_any_branch_available()
    {
        $cafe = Cafe::factory()->create();

        // One closed branch, one available branch
        Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => false,
            'total_seats' => 100,
        ]);
        Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => true,
            'total_seats' => 100,
        ]);

        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200);
        $cafeData = collect($response->json('data.data'))->firstWhere('id', $cafe->id);
        $this->assertEquals('available', $cafeData['current_status']);
        $this->assertEquals('green', $cafeData['status_color']);
    }

    /** @test */
    public function cafe_shows_closed_when_all_branches_closed()
    {
        $cafe = Cafe::factory()->create();

        Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'is_open' => false,
        ]);

        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200);
        $cafeData = collect($response->json('data.data'))->firstWhere('id', $cafe->id);
        $this->assertEquals('closed', $cafeData['current_status']);
        $this->assertEquals('gray', $cafeData['status_color']);
    }
}
