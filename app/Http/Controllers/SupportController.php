<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/support/contact
     * Submit a contact/support message.
     */
    public function contact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return $this->successResponse([
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'message' => $ticket->message,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
            ],
        ], 'Support ticket created successfully.', 201);
    }

    /**
     * POST /api/v1/support/report-issue
     * Report an issue with optional screenshot.
     */
    public function reportIssue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'screenshot' => ['nullable', 'file', 'image', 'max:5120'], // 5MB max
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('support/screenshots', 'public');
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['title'],
            'message' => $validated['description'],
            'screenshot' => $screenshotPath,
            'status' => 'open',
        ]);

        return $this->successResponse([
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'message' => $ticket->message,
                'screenshot' => $screenshotPath ? [
                    'original' => Storage::url($screenshotPath),
                    'medium' => Storage::url($screenshotPath),
                    'thumbnail' => Storage::url($screenshotPath),
                ] : null,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at,
            ],
        ], 'Issue reported successfully.', 201);
    }

    /**
     * GET /api/v1/support/my-tickets
     * List user's support tickets.
     */
    public function myTickets(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'message' => $ticket->message,
                    'screenshot' => $ticket->screenshot ? [
                        'original' => Storage::url($ticket->screenshot),
                        'medium' => Storage::url($ticket->screenshot),
                        'thumbnail' => Storage::url($ticket->screenshot),
                    ] : null,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at,
                    'updated_at' => $ticket->updated_at,
                ];
            });

        return $this->successResponse([
            'tickets' => $tickets,
        ], 'Tickets retrieved successfully.');
    }
}
