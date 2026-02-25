<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Models\ChatRoom;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\GameMatch;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatRoomTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_gets_or_creates_public_chat_room()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/chat/rooms/public');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'type', 'name'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['type' => 'public'],
            ]);

        $this->assertDatabaseHas('chat_rooms', [
            'type' => 'public',
        ]);
    }

    /** @test */
    public function it_gets_cafe_chat_room_with_valid_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/chat/rooms/cafe/{$cafe->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'type', 'cafe_id'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['type' => 'cafe'],
            ]);
    }

    /** @test */
    public function it_returns_403_for_cafe_chat_without_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/chat/rooms/cafe/{$cafe->id}");

        $response->assertStatus(403)
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
    public function it_lists_user_chat_rooms()
    {
        $user = User::factory()->create();
        $room1 = ChatRoom::factory()->create(['type' => 'public']);
        $room2 = ChatRoom::factory()->create(['type' => 'cafe']);
        
        $user->chatRooms()->attach([$room1->id, $room2->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/chat/rooms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'type', 'name'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_room_access()
    {
        $response = $this->getJson('/api/v1/chat/rooms/public');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_gets_match_specific_chat_room()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/chat/rooms/match/{$match->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'type', 'match_id'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['type' => 'match'],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_cafe()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/chat/rooms/cafe/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}
