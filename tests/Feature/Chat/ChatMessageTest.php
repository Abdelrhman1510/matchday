<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_message_successfully()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create(['type' => 'public']);
        $user->chatRooms()->attach($room->id);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => 'Hello everyone!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'message', 'user', 'created_at'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['message' => 'Hello everyone!'],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => 'Hello everyone!',
        ]);
    }

    /** @test */
    public function it_returns_422_for_empty_message()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $user->chatRooms()->attach($room->id);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['message'],
            ]);
    }

    /** @test */
    public function it_returns_422_for_message_exceeding_max_length()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $user->chatRooms()->attach($room->id);
        Sanctum::actingAs($user);

        $longMessage = str_repeat('a', 1001);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => $longMessage,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['message'],
            ]);
    }

    /** @test */
    public function it_sends_message_with_valid_emoji()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $user->chatRooms()->attach($room->id);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => 'Great match! 🎉⚽',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['message' => 'Great match! 🎉⚽'],
            ]);
    }

    /** @test */
    public function it_lists_room_messages()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $user->chatRooms()->attach($room->id);
        
        ChatMessage::factory()->count(5)->create(['room_id' => $room->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/chat/rooms/{$room->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'message', 'user', 'created_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_returns_403_for_non_member_sending_message()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create(['type' => 'cafe']);
        // User not added to cafe room - should be denied
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_message()
    {
        $room = ChatRoom::factory()->create();

        $response = $this->postJson("/api/v1/chat/rooms/{$room->id}/messages", [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_paginates_messages()
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $user->chatRooms()->attach($room->id);
        
        ChatMessage::factory()->count(30)->create(['room_id' => $room->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/chat/rooms/{$room->id}/messages?per_page=10");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data.data'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_room()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/chat/rooms/99999/messages', [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}
