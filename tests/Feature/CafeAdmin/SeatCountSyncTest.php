<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\SeatingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeatCountSyncTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function updated_seat_count_is_reflected_in_branch_overview()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create([
            'branch_id' => $branch->id, 'name' => 'Main Screen', 'total_seats' => 9,
        ]);
        Sanctum::actingAs($owner);

        // First overview read populates the 5-minute cache with total_seats = 9.
        $r1 = $this->getJson("/api/v1/cafe-admin/branches/{$branch->id}/overview")->assertStatus(200);
        $this->assertEquals(9, collect($r1->json('data.sections'))->firstWhere('id', $section->id)['total_seats']);

        // Update the seat count in Seat Management: 9 -> 20.
        $this->putJson("/api/v1/cafe-admin/sections/{$section->id}", ['total_seats' => 20])
            ->assertStatus(200);

        // Seating & Facilities (branch overview) must now show 20, not the stale 9.
        $r2 = $this->getJson("/api/v1/cafe-admin/branches/{$branch->id}/overview")->assertStatus(200);
        $this->assertEquals(20, collect($r2->json('data.sections'))->firstWhere('id', $section->id)['total_seats']);
    }
}
