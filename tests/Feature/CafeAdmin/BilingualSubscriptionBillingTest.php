<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BilingualSubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function subscription_plans_include_arabic_fields()
    {
        SubscriptionPlan::factory()->create([
            'is_active' => true,
            'name' => 'Pro',
            'name_ar' => 'احترافي',
            'currency' => 'SAR',
            'features' => ['Analytics', 'Branding'],
            'features_ar' => ['تحليلات', 'علامة تجارية'],
        ]);

        $res = $this->getJson('/api/v1/subscription/plans')->assertStatus(200);

        $plan = $res->json('data.0');
        $this->assertSame('احترافي', $plan['name_ar']);
        $this->assertSame(['تحليلات', 'علامة تجارية'], $plan['features_ar']);
        $this->assertSame('ريال سعودي', $plan['currency_ar']);
    }

    /** @test */
    public function current_subscription_plan_includes_arabic_fields()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
            'name' => 'Pro',
            'name_ar' => 'احترافي',
            'currency' => 'SAR',
            'features' => ['Analytics'],
            'features_ar' => ['تحليلات'],
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
        ]);
        Sanctum::actingAs($owner);

        $res = $this->getJson('/api/v1/cafe-admin/subscription')->assertStatus(200);

        $this->assertSame('احترافي', $res->json('data.plan.name_ar'));
        $this->assertSame(['تحليلات'], $res->json('data.plan.features_ar'));
        $this->assertSame('ريال سعودي', $res->json('data.plan.currency_ar'));
    }

    /** @test */
    public function billing_transactions_include_arabic_fields()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Payment::factory()->create([
            'user_id' => $owner->id,
            'booking_id' => null,
            'type' => 'subscription',
            'currency' => 'SAR',
            'status' => 'paid',
            'description' => null,
        ]);
        Sanctum::actingAs($owner);

        $res = $this->getJson('/api/v1/cafe-admin/billing?period=all_time&type=subscription')
            ->assertStatus(200);

        $txn = $res->json('data.0');
        $this->assertSame('دفع الاشتراك', $txn['title_ar']);
        $this->assertSame('تجديد الاشتراك', $txn['subtitle_ar']);
        $this->assertSame('ريال سعودي', $txn['currency_ar']);
    }
}
