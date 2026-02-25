<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_user_notifications()
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'type', 'title', 'body', 'read_at', 'created_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_returns_unread_count()
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['unread_count'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['unread_count' => 3],
            ]);
    }

    /** @test */
    public function it_marks_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function it_marks_all_notifications_as_read()
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $unreadCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_deletes_notification()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function it_updates_notification_settings()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/notifications/settings', [
            'email_notifications' => true,
            'push_notifications' => false,
            'booking_reminders' => true,
            'match_updates' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email_notifications',
                    'push_notifications',
                    'booking_reminders',
                    'match_updates',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_gets_notification_settings()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email_notifications',
                    'push_notifications',
                    'booking_reminders',
                    'match_updates',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_403_for_other_user_notification()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user1->id]);
        
        Sanctum::actingAs($user2);

        $response = $this->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_notification_access()
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_filters_notifications_by_type()
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'type' => 'booking',
        ]);
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'type' => 'match',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications?type=booking');

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_notification()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/notifications/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }
}
