<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_current_subscription()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/cafes/{$cafe->id}/subscription");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'plan' => ['name', 'price', 'features'],
                    'status',
                    'current_period_end',
                    'auto_renew',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'active'],
            ]);
    }

    /** @test */
    public function it_lists_available_subscription_plans()
    {
        $owner = User::factory()->cafeOwner()->create();
        
        SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        SubscriptionPlan::factory()->create(['name' => 'Pro', 'price' => 59.99]);
        SubscriptionPlan::factory()->create(['name' => 'Enterprise', 'price' => 99.99]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/admin/subscription/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'price', 'features', 'billing_period'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_upgrades_subscription_plan()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $basicPlan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        $proPlan = SubscriptionPlan::factory()->create(['name' => 'Pro', 'price' => 59.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $basicPlan->id,
            'status' => 'active',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/upgrade", [
            'plan_id' => $proPlan->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $subscription->refresh();
        $this->assertEquals($proPlan->id, $subscription->plan_id);
    }

    /** @test */
    public function it_downgrades_subscription_plan()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $proPlan = SubscriptionPlan::factory()->create(['name' => 'Pro', 'price' => 59.99]);
        $basicPlan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $proPlan->id,
            'status' => 'active',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/downgrade", [
            'plan_id' => $basicPlan->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $subscription->refresh();
        // Downgrade scheduled for next billing period
        $this->assertEquals($basicPlan->id, $subscription->scheduled_plan_id);
    }

    /** @test */
    public function it_cancels_subscription()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
    }

    /** @test */
    public function it_returns_billing_history()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
        ]);
        
        Payment::factory()->count(5)->create([
            'subscription_id' => $subscription->id,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/cafes/{$cafe->id}/subscription/billing-history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'amount', 'status', 'created_at', 'invoice_url'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_resumes_cancelled_subscription()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'cancelled',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/subscription/resume");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }

    /** @test */
    public function it_handles_payment_failure()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create(['name' => 'Basic', 'price' => 29.99]);
        
        $subscription = Subscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        
        Sanctum::actingAs($owner);

        // Simulate payment failure notification
        $response = $this->postJson('/api/v1/webhooks/subscription/payment-failed', [
            'subscription_id' => $subscription->id,
            'reason' => 'Card declined',
        ]);

        $subscription->refresh();
        $this->assertEquals('past_due', $subscription->status);
    }

    /** @test */
    public function it_returns_403_for_non_owner_accessing_subscription()
    {
        $otherOwner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create();
        Sanctum::actingAs($otherOwner);

        $response = $this->getJson("/api/v1/admin/cafes/{$cafe->id}/subscription");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_updates_payment_method()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/cafes/{$cafe->id}/subscription/payment-method", [
            'payment_method_id' => 'pm_card_visa',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('cafes', [
            'id' => $cafe->id,
            'payment_method_id' => 'pm_card_visa',
        ]);
    }
}
