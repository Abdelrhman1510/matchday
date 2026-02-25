<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatMessage $chatMessage)
    {
        $this->chatMessage = $chatMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat.' . $this->chatMessage->room_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->chatMessage->id,
                'message' => $this->chatMessage->message,
                'type' => $this->chatMessage->type,
                'created_at' => $this->chatMessage->created_at->toIso8601String(),
            ],
            'user' => [
                'id' => $this->chatMessage->user->id,
                'name' => $this->chatMessage->user->name,
                'avatar' => $this->chatMessage->user->avatar,
                'favorite_team' => $this->chatMessage->user->fanProfile?->favoriteTeam ? [
                    'id' => $this->chatMessage->user->fanProfile->favoriteTeam->id,
                    'name' => $this->chatMessage->user->fanProfile->favoriteTeam->name,
                    'logo' => $this->chatMessage->user->fanProfile->favoriteTeam->logo,
                ] : null,
            ],
            'room_id' => $this->chatMessage->room_id,
        ];
    }
}
