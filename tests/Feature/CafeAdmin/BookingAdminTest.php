<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\GameMatch;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_branch_bookings()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        Booking::factory()->count(5)->create(['match_id' => $match->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'booking_code', 'user', 'status'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_checks_in_booking_with_qr_scan()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'match_id' => $match->id,
            'booking_code' => 'BOOK-ABC123',
            'status' => 'confirmed',
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/bookings/{$booking->id}/check-in");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked_in',
        ]);
    }

    /** @test */
    public function it_scans_qr_code_for_check_in()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'match_id' => $match->id,
            'booking_code' => 'BOOK-XYZ789',
            'status' => 'confirmed',
        ]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/admin/bookings/scan-qr', [
            'qr_data' => $booking->qr_code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['booking', 'check_in_time'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'checked_in',
        ]);
    }

    /** @test */
    public function it_returns_today_bookings_summary()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now(),
        ]);
        
        Booking::factory()->count(3)->create([
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        Booking::factory()->count(2)->create([
            'match_id' => $match->id,
            'status' => 'checked_in',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings/today");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_bookings',
                    'checked_in',
                    'pending',
                    'revenue',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_bookings' => 5,
                    'checked_in' => 2,
                    'pending' => 3,
                ],
            ]);
    }

    /** @test */
    public function it_filters_bookings_by_status()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        
        Booking::factory()->count(3)->create([
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        Booking::factory()->count(2)->create([
            'match_id' => $match->id,
            'status' => 'checked_in',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings?status=confirmed");

        $response->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_booking_details_for_admin()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create(['match_id' => $match->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_code',
                    'qr_code',
                    'user',
                    'match',
                    'seats',
                    'payment',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_qr_code()
    {
        $owner = User::factory()->cafeOwner()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/admin/bookings/scan-qr', [
            'qr_data' => 'INVALID-QR',
        ]);

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
    public function it_returns_422_for_already_checked_in_booking()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'match_id' => $match->id,
            'status' => 'checked_in',
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/bookings/{$booking->id}/check-in");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_403_for_fan_accessing_booking_admin()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        Sanctum::actingAs($fan);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_exports_bookings_report()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        Booking::factory()->count(10)->create(['match_id' => $match->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings/export");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['download_url'],
            ]);
    }
}
