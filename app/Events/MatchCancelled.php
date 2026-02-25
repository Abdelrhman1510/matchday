<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GameMatch $match;
    public int $bookingsCancelled;

    /**
     * Create a new event instance.
     */
    public function __construct(GameMatch $match, int $bookingsCancelled = 0)
    {
        $this->match = $match;
        $this->bookingsCancelled = $bookingsCancelled;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('matches'),
            new Channel("match.{$this->match->id}"),
            new Channel("branch.{$this->match->branch_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MatchCancelled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $this->match->load(['homeTeam', 'awayTeam']);

        return [
            'match_id' => $this->match->id,
            'home_team' => $this->match->homeTeam->name,
            'away_team' => $this->match->awayTeam->name,
            'status' => 'cancelled',
            'bookings_cancelled' => $this->bookingsCancelled,
        ];
    }
}
