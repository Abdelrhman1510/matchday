<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\GameMatch;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_dashboard_overview()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        // Create test data
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        Booking::factory()->count(5)->create(['match_id' => $match->id]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_bookings',
                    'total_revenue',
                    'active_matches',
                    'average_occupancy',
                    'recent_bookings',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_revenue_statistics()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create(['match_id' => $match->id]);
        Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 500,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/revenue");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_revenue',
                    'this_month',
                    'last_month',
                    'growth_percentage',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_revenue_by_period()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/revenue?period=month");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'period',
                    'revenue',
                    'bookings_count',
                ],
            ]);
    }

    /** @test */
    public function it_returns_booking_trends()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        Booking::factory()->count(10)->create([
            'match_id' => $match->id,
            'created_at' => now()->subDays(5),
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/bookings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_bookings',
                    'trend_data' => [
                        '*' => ['date', 'count'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_chart_data_for_revenue()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/charts/revenue?period=week");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'labels',
                    'datasets' => [
                        '*' => ['label', 'data'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_top_performing_matches()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $match1 = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $match2 = GameMatch::factory()->create(['branch_id' => $branch->id]);
        
        Booking::factory()->count(10)->create(['match_id' => $match1->id]);
        Booking::factory()->count(5)->create(['match_id' => $match2->id]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/top-matches");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['match', 'bookings_count', 'revenue'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_occupancy_rate()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/occupancy");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'average_occupancy',
                    'peak_occupancy',
                    'occupancy_by_day' => [
                        '*' => ['day', 'occupancy_rate'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_filters_analytics_by_date_range()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/revenue?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_403_for_fan_accessing_analytics()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        Sanctum::actingAs($fan);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/analytics/dashboard");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_exports_analytics_report()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/analytics/export", [
            'period' => 'month',
            'format' => 'pdf',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['download_url'],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }
}
