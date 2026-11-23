<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Review;
use App\Models\Seat;
use App\Models\SeatingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchSchemaFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $this->owner->id]);
        $this->branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($this->owner);
    }

    private function bookingOn(string $date, string $matchStatus, array $booking = []): Booking
    {
        $match = GameMatch::factory()->create([
            'branch_id' => $this->branch->id, 'match_date' => $date, 'status' => $matchStatus,
        ]);

        return Booking::factory()->create($booking + [
            'match_id' => $match->id, 'branch_id' => $this->branch->id, 'status' => 'confirmed',
        ]);
    }

    public function test_branch_with_an_upcoming_booking_cannot_be_deleted(): void
    {
        $this->bookingOn(now()->addDay()->toDateString(), 'upcoming');

        $this->deleteJson("/api/v1/cafe-admin/branches/{$this->branch->id}")->assertStatus(400);
        $this->assertNotSoftDeleted('branches', ['id' => $this->branch->id]);
    }

    public function test_pay_at_cafe_booking_also_blocks_deletion(): void
    {
        $this->bookingOn(now()->addDay()->toDateString(), 'upcoming', ['status' => 'pending', 'payment_method' => 'pay_at_cafe']);

        $this->deleteJson("/api/v1/cafe-admin/branches/{$this->branch->id}")->assertStatus(400);
    }

    public function test_past_cancelled_and_expired_bookings_do_not_block_deletion(): void
    {
        $this->bookingOn(now()->subDays(3)->toDateString(), 'finished');
        $this->bookingOn(now()->addDay()->toDateString(), 'upcoming', ['status' => 'cancelled']);
        $this->bookingOn(now()->addDay()->toDateString(), 'upcoming', [
            'status' => 'pending', 'payment_method' => 'online', 'payment_expires_at' => now()->subMinute(),
        ]);

        $this->deleteJson("/api/v1/cafe-admin/branches/{$this->branch->id}")->assertOk();
        $this->assertSoftDeleted('branches', ['id' => $this->branch->id]);
    }

    public function test_reviews_list_returns_the_users_avatar_as_profile_picture(): void
    {
        $withUrl = User::factory()->create(['avatar' => ['original' => 'https://cdn.test/o.jpg', 'medium' => 'https://cdn.test/m.jpg']]);
        $withPath = User::factory()->create(['avatar' => ['original' => 'avatars/original/a.jpg']]);
        $without = User::factory()->create(['avatar' => null]);
        foreach ([$withUrl, $withPath, $without] as $user) {
            Review::factory()->create(['branch_id' => $this->branch->id, 'user_id' => $user->id]);
        }

        $pictures = collect($this->getJson("/api/v1/branches/{$this->branch->id}/reviews")->assertOk()->json('data'))
            ->pluck('user.profile_picture', 'user.id');

        $this->assertSame('https://cdn.test/m.jpg', $pictures[$withUrl->id]);
        $this->assertSame(url('storage/avatars/original/a.jpg'), $pictures[$withPath->id]);
        $this->assertNull($pictures[$without->id]);
    }

    public function test_hours_accept_numbers_and_day_names(): void
    {
        $this->putJson("/api/v1/cafe-admin/branches/{$this->branch->id}/hours", ['hours' => [
            ['day_of_week' => 0, 'open_time' => '09:00', 'close_time' => '23:00'],
            ['day_of_week' => '2', 'open_time' => '09:00', 'close_time' => '23:00'],
            ['day_of_week' => 'Monday', 'open_time' => '09:00', 'close_time' => '23:00'],
            ['day_of_week' => 'saturday', 'is_open' => false],
        ]])->assertOk();

        $this->assertEqualsCanonicalizing(
            [0, 1, 2, 6],
            BranchHour::where('branch_id', $this->branch->id)->pluck('day_of_week')->map(fn ($d) => (int) $d)->all()
        );
    }

    public function test_invalid_or_repeated_days_are_rejected_and_keep_saved_hours(): void
    {
        BranchHour::factory()->create(['branch_id' => $this->branch->id, 'day_of_week' => 3]);

        foreach ([
            [['day_of_week' => 7, 'open_time' => '09:00', 'close_time' => '23:00']],
            [['day_of_week' => 'Someday', 'open_time' => '09:00', 'close_time' => '23:00']],
            [
                ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '23:00'],
                ['day_of_week' => 'Monday', 'open_time' => '10:00', 'close_time' => '22:00'],
            ],
        ] as $hours) {
            $this->putJson("/api/v1/cafe-admin/branches/{$this->branch->id}/hours", ['hours' => $hours])->assertStatus(422);
        }

        $this->assertSame(1, BranchHour::where('branch_id', $this->branch->id)->where('day_of_week', 3)->count());
    }

    public function test_seat_label_up_to_50_characters_is_saved(): void
    {
        $section = SeatingSection::factory()->create(['branch_id' => $this->branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id]);
        $label = str_repeat('A', 50);

        $this->putJson("/api/v1/cafe-admin/seats/{$seat->id}", ['label' => $label])->assertOk();
        $this->assertSame($label, $seat->fresh()->label);

        $this->putJson("/api/v1/cafe-admin/seats/{$seat->id}", ['label' => str_repeat('A', 51)])->assertStatus(422);
    }
}
