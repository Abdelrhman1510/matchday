<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\Offer;
use App\Models\StaffMember;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Cafe $cafe;
    private SubscriptionPlan $starterPlan;
    private SubscriptionPlan $proPlan;
    private SubscriptionEnforcementService $enforcement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->cafeOwner()->create();
        $this->cafe = Cafe::factory()->create(['owner_id' => $this->owner->id]);

        // Starter plan with strict limits
        $this->starterPlan = SubscriptionPlan::factory()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price' => 29.99,
            'max_branches' => 1,
            'max_matches_per_month' => 5,
            'max_bookings_per_month' => 50,
            'max_staff_members' => 2,
            'max_offers' => 3,
            'has_analytics' => false,
            'has_chat' => false,
            'has_qr_scanner' => true,
            'has_occupancy_tracking' => false,
            'has_branding' => false,
            'has_priority_support' => false,
            'is_active' => true,
        ]);

        // Pro plan with higher limits
        $this->proPlan = SubscriptionPlan::factory()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 59.99,
            'max_branches' => 5,
            'max_matches_per_month' => 30,
            'max_bookings_per_month' => 300,
            'max_staff_members' => 10,
            'max_offers' => 15,
            'has_analytics' => true,
            'has_chat' => true,
            'has_qr_scanner' => true,
            'has_occupancy_tracking' => true,
            'has_branding' => true,
            'has_priority_support' => false,
            'is_active' => true,
        ]);

        $this->enforcement = app(SubscriptionEnforcementService::class);
    }

    // ─── Helper: Create active subscription ──────────────────────────────────

    private function createSubscription(
        ?SubscriptionPlan $plan = null,
        string $status = 'active',
        ?\Carbon\Carbon $expiresAt = null
    ): CafeSubscription {
        return CafeSubscription::factory()->create([
            'cafe_id' => $this->cafe->id,
            'plan_id' => ($plan ?? $this->starterPlan)->id,
            'status' => $status,
            'starts_at' => now()->subMonth(),
            'expires_at' => $expiresAt ?? now()->addMonth(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  BRANCH LIMIT ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_allows_creating_branch_within_limit()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->canCreateBranch($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['limit']);
        $this->assertEquals(0, $result['current']);
    }

    /** @test */
    public function it_blocks_creating_branch_at_limit()
    {
        $this->createSubscription($this->starterPlan);
        Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        $result = $this->enforcement->canCreateBranch($this->cafe);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Branch limit reached', $result['reason']);
        $this->assertEquals(1, $result['limit']);
        $this->assertEquals(1, $result['current']);
    }

    /** @test */
    public function it_allows_creating_branch_on_higher_plan()
    {
        $this->createSubscription($this->proPlan);
        Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        $result = $this->enforcement->canCreateBranch($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(1, $result['current']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  MATCH LIMIT ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_allows_creating_match_within_limit()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->canCreateMatch($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(0, $result['current']);
    }

    /** @test */
    public function it_blocks_creating_match_at_monthly_limit()
    {
        $this->createSubscription($this->starterPlan);
        $branch = Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        // Create 5 matches this month
        for ($i = 0; $i < 5; $i++) {
            GameMatch::factory()->create([
                'branch_id' => $branch->id,
                'created_at' => now(),
            ]);
        }

        $result = $this->enforcement->canCreateMatch($this->cafe);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('match limit reached', $result['reason']);
        $this->assertEquals(5, $result['limit']);
        $this->assertEquals(5, $result['current']);
    }

    /** @test */
    public function it_resets_match_count_for_new_month()
    {
        $this->createSubscription($this->starterPlan);
        $branch = Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        // Create matches last month — should not count
        for ($i = 0; $i < 5; $i++) {
            GameMatch::factory()->create([
                'branch_id' => $branch->id,
                'created_at' => now()->subMonth(),
            ]);
        }

        $result = $this->enforcement->canCreateMatch($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(0, $result['current']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  STAFF LIMIT ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_allows_adding_staff_within_limit()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->canAddStaff($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['limit']);
    }

    /** @test */
    public function it_blocks_adding_staff_at_limit()
    {
        $this->createSubscription($this->starterPlan);

        // Create staff members via StaffMember model (how canAddStaff counts)
        for ($i = 0; $i < 2; $i++) {
            $staffUser = User::factory()->create(['role' => 'staff']);
            StaffMember::create([
                'cafe_id' => $this->cafe->id,
                'user_id' => $staffUser->id,
                'role' => 'staff',
                'invitation_status' => 'accepted',
                'invited_by' => $this->owner->id,
            ]);
        }

        $result = $this->enforcement->canAddStaff($this->cafe);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Staff limit reached', $result['reason']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  OFFER LIMIT ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_allows_creating_offer_within_limit()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->canCreateOffer($this->cafe);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(3, $result['limit']);
    }

    /** @test */
    public function it_blocks_creating_offer_at_limit()
    {
        $this->createSubscription($this->starterPlan);
        $branch = Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        for ($i = 0; $i < 3; $i++) {
            Offer::factory()->create([
                'cafe_id' => $this->cafe->id,
                'branch_id' => $branch->id,
                'status' => 'active',
            ]);
        }

        $result = $this->enforcement->canCreateOffer($this->cafe);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Offer limit reached', $result['reason']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  FEATURE FLAG ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_blocks_analytics_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_analytics');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_allows_analytics_on_pro_plan()
    {
        $this->createSubscription($this->proPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_analytics');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_blocks_occupancy_tracking_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_occupancy_tracking');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_allows_occupancy_tracking_on_pro_plan()
    {
        $this->createSubscription($this->proPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_occupancy_tracking');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_allows_qr_scanner_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_qr_scanner');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_blocks_chat_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_chat');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_allows_chat_on_pro_plan()
    {
        $this->createSubscription($this->proPlan);

        $result = $this->enforcement->hasFeature($this->cafe, 'has_chat');

        $this->assertTrue($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  GRACE PERIOD ENFORCEMENT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_detects_grace_period_for_recently_expired_subscription()
    {
        // Expired 3 days ago — within 7-day grace period
        $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDays(3)
        );

        $result = $this->enforcement->isInGracePeriod($this->cafe);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_reports_grace_period_days_remaining()
    {
        // Expired 3 days ago — 4 days of grace remain
        $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDays(3)
        );

        $daysLeft = $this->enforcement->getGracePeriodDaysLeft($this->cafe);

        $this->assertGreaterThan(0, $daysLeft);
        $this->assertLessThanOrEqual(7, $daysLeft);
    }

    /** @test */
    public function it_is_not_in_grace_period_for_fully_expired_subscription()
    {
        // Expired 10 days ago — past 7-day grace period
        $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDays(10)
        );

        $result = $this->enforcement->isInGracePeriod($this->cafe);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_still_enforces_limits_during_grace_period()
    {
        // Expired 2 days ago — in grace period
        $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDays(2)
        );
        Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        $result = $this->enforcement->canCreateBranch($this->cafe);

        // Should still enforce limits during grace period
        $this->assertFalse($result['allowed']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  EXPIRED / NO SUBSCRIPTION
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_blocks_when_no_subscription_exists()
    {
        // No subscription created for the cafe
        $result = $this->enforcement->canCreateBranch($this->cafe);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('No active subscription', $result['reason']);
    }

    /** @test */
    public function it_blocks_features_when_subscription_is_expired()
    {
        // Expired 10 days ago, past grace period
        $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDays(10)
        );

        $result = $this->enforcement->hasFeature($this->cafe, 'has_qr_scanner');

        $this->assertFalse($result);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  USAGE SUMMARY
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_returns_usage_summary()
    {
        $this->createSubscription($this->starterPlan);
        Branch::factory()->create(['cafe_id' => $this->cafe->id]);

        $summary = $this->enforcement->getUsageSummary($this->cafe);

        // Top-level keys
        $this->assertArrayHasKey('plan', $summary);
        $this->assertArrayHasKey('usage', $summary);
        $this->assertArrayHasKey('features', $summary);
        $this->assertArrayHasKey('grace_period', $summary);

        // Plan info
        $this->assertNotNull($summary['plan']);
        $this->assertEquals('Starter', $summary['plan']['name']);

        // Usage structure
        $this->assertArrayHasKey('branches', $summary['usage']);
        $this->assertArrayHasKey('matches_this_month', $summary['usage']);
        $this->assertArrayHasKey('bookings_this_month', $summary['usage']);
        $this->assertArrayHasKey('staff_members', $summary['usage']);
        $this->assertArrayHasKey('offers', $summary['usage']);

        $this->assertEquals(1, $summary['usage']['branches']['current']);
        $this->assertEquals(1, $summary['usage']['branches']['limit']);
    }

    /** @test */
    public function usage_summary_returns_null_plan_when_no_subscription()
    {
        $summary = $this->enforcement->getUsageSummary($this->cafe);

        $this->assertNull($summary['plan']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  USAGE API ENDPOINT
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_returns_usage_from_api_endpoint()
    {
        $this->createSubscription($this->starterPlan);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/cafe-admin/subscription/usage');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'plan',
                    'usage' => [
                        'branches',
                        'matches_this_month',
                        'bookings_this_month',
                        'staff_members',
                        'offers',
                    ],
                    'features',
                ],
            ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  CHECK EXPIRED SUBSCRIPTIONS COMMAND
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function it_marks_expired_subscriptions_via_command()
    {
        $subscription = $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->subDay() // Expired yesterday
        );

        Artisan::call('subscriptions:check-expired');

        $subscription->refresh();
        $this->assertEquals('expired', $subscription->status);
    }

    /** @test */
    public function it_does_not_mark_active_subscriptions_as_expired()
    {
        $subscription = $this->createSubscription(
            $this->starterPlan,
            'active',
            now()->addMonth() // Still active
        );

        Artisan::call('subscriptions:check-expired');

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  MIDDLEWARE ENFORCEMENT (via HTTP)
    // ═══════════════════════════════════════════════════════════════════════════

    /** @test */
    public function middleware_blocks_analytics_route_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/cafe-admin/analytics/overview');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function middleware_allows_analytics_route_on_pro_plan()
    {
        $this->createSubscription($this->proPlan);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/cafe-admin/analytics/overview');

        // Should not return 403 (it may 200 or other status depending on data)
        $this->assertNotEquals(403, $response->status());
    }

    /** @test */
    public function middleware_blocks_occupancy_route_on_starter_plan()
    {
        $this->createSubscription($this->starterPlan);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/v1/cafe-admin/occupancy');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }
}
