<?php

namespace Tests\Feature\Support;

use App\Models\User;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_submits_contact_ticket()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support/contact', [
            'subject' => 'Booking issue',
            'message' => 'I cannot find my booking confirmation.',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ticket' => ['id', 'subject', 'message', 'status', 'created_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'ticket' => [
                        'subject' => 'Booking issue',
                        'status' => 'open',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'subject' => 'Booking issue',
            'status' => 'open',
        ]);
    }

    /** @test */
    public function it_validates_contact_fields()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support/contact', []);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_requires_auth_for_contact()
    {
        $response = $this->postJson('/api/v1/support/contact', [
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_reports_issue_without_screenshot()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support/report-issue', [
            'title' => 'App crash on booking',
            'description' => 'The app crashes when I try to confirm a booking.',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ticket' => ['id', 'subject', 'message', 'screenshot', 'status', 'created_at'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'ticket' => [
                        'subject' => 'App crash on booking',
                        'screenshot' => null,
                        'status' => 'open',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_reports_issue_with_screenshot()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('screenshot.jpg', 800, 600);

        $response = $this->postJson('/api/v1/support/report-issue', [
            'title' => 'UI bug',
            'description' => 'Button is misaligned on the booking page.',
            'screenshot' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'ticket' => [
                        'id',
                        'subject',
                        'message',
                        'screenshot' => ['original', 'medium', 'thumbnail'],
                        'status',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'subject' => 'UI bug',
        ]);

        $ticket = SupportTicket::where('user_id', $user->id)->first();
        $this->assertNotNull($ticket->screenshot);
    }

    /** @test */
    public function it_validates_report_issue_fields()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support/report-issue', []);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_lists_user_tickets()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'Ticket 1',
            'message' => 'Message 1',
            'status' => 'open',
        ]);
        SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'Ticket 2',
            'message' => 'Message 2',
            'status' => 'resolved',
        ]);
        SupportTicket::create([
            'user_id' => $otherUser->id,
            'subject' => 'Other Ticket',
            'message' => 'Other message',
            'status' => 'open',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/support/my-tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tickets' => [
                        '*' => ['id', 'subject', 'message', 'screenshot', 'status', 'created_at', 'updated_at'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.tickets'));
    }

    /** @test */
    public function it_returns_tickets_ordered_by_newest_first()
    {
        $user = User::factory()->create();

        $oldTicket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'Old Ticket',
            'message' => 'Old message',
            'status' => 'open',
        ]);
        $oldTicket->created_at = now()->subDays(5);
        $oldTicket->save();

        $newTicket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => 'New Ticket',
            'message' => 'New message',
            'status' => 'open',
        ]);
        $newTicket->created_at = now()->addMinute();
        $newTicket->save();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/support/my-tickets');

        $response->assertStatus(200);

        $tickets = $response->json('data.tickets');
        $this->assertCount(2, $tickets);
        $this->assertEquals('New Ticket', $tickets[0]['subject']);
        $this->assertEquals('Old Ticket', $tickets[1]['subject']);
    }

    /** @test */
    public function it_requires_auth_for_my_tickets()
    {
        $response = $this->getJson('/api/v1/support/my-tickets');

        $response->assertStatus(401);
    }
}
