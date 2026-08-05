<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\SeatingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArabicMessagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function seating_success_message_is_arabic_when_locale_is_arabic()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id, 'total_seats' => 9]);
        Sanctum::actingAs($owner);

        $this->putJson(
            "/api/v1/cafe-admin/sections/{$section->id}",
            ['total_seats' => 12],
            ['Accept-Language' => 'ar']
        )->assertStatus(200)->assertJsonPath('message', 'تم تحديث القسم بنجاح');
    }

    /** @test */
    public function seating_success_message_stays_english_by_default()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id, 'total_seats' => 9]);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/cafe-admin/sections/{$section->id}", ['total_seats' => 12])
            ->assertStatus(200)->assertJsonPath('message', 'Section updated successfully');
    }

    /** @test */
    public function messages_use_the_users_saved_locale_when_no_header_is_sent()
    {
        // Root cause of BUG-037: the app sets the user's language but may not send
        // Accept-Language. Responses must still be Arabic from the saved preference.
        $owner = User::factory()->cafeOwner()->create(['locale' => 'ar']);
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id, 'total_seats' => 9]);
        $token = $owner->createToken('test')->plainTextToken;

        // Real bearer token, no Accept-Language header: locale comes from user->locale.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/cafe-admin/sections/{$section->id}", ['total_seats' => 15])
            ->assertStatus(200)->assertJsonPath('message', 'تم تحديث القسم بنجاح');
    }

    /** @test */
    public function cafe_update_message_is_localized()
    {
        $owner = User::factory()->cafeOwner()->create(['locale' => 'ar']);
        Cafe::factory()->create(['owner_id' => $owner->id]);
        $token = $owner->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/cafe-admin/cafe', ['name' => 'مقهى'])
            ->assertStatus(200)->assertJsonPath('message', 'تم تحديث المقهى بنجاح');
    }
}
