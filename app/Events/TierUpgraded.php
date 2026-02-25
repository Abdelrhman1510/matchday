<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TierUpgraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $oldTier;
    public string $newTier;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $oldTier, string $newTier)
    {
        $this->user = $user;
        $this->oldTier = $oldTier;
        $this->newTier = $newTier;
    }
}
