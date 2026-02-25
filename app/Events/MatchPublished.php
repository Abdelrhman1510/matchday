<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchPublished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GameMatch $match;

    /**
     * Create a new event instance.
     */
    public function __construct(GameMatch $match)
    {
        $this->match = $match;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('matches'),
            new Channel("branch.{$this->match->branch_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MatchPublished';
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
            'match_date' => $this->match->match_date->format('Y-m-d'),
            'kick_off' => $this->match->kick_off,
            'branch_id' => $this->match->branch_id,
        ];
    }
}
