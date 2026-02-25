<?php

namespace App\Events;

use App\Models\User;
use App\Models\LoyaltyTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointsEarned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public int $points;
    public string $description;
    public LoyaltyTransaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, int $points, string $description, LoyaltyTransaction $transaction)
    {
        $this->user = $user;
        $this->points = $points;
        $this->description = $description;
        $this->transaction = $transaction;
    }
}
