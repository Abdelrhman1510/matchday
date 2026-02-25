<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if user has unlocked this achievement
        $isUnlocked = false;
        $unlockedAt = null;

        if ($request->user()) {
            $pivot = $this->users()
                ->where('user_id', $request->user()->id)
                ->first();

            if ($pivot) {
                $isUnlocked = true;
                $unlockedAt = $pivot->pivot->unlocked_at;
            }
        }

        // Convert unlocked_at string to Carbon if needed
        $unlockedCarbon = $unlockedAt ? \Carbon\Carbon::parse($unlockedAt) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon ? url('storage/' . $this->icon) : null,
            'criteria_type' => $this->criteria_type,
            'criteria_value' => $this->criteria_value,
            'points_reward' => $this->points_reward,
            'requirement' => $this->requirement,
            'category' => $this->category,
            'is_unlocked' => $isUnlocked,
            'unlocked_at' => $unlockedCarbon?->format('Y-m-d H:i:s'),
            'unlocked_at_human' => $unlockedCarbon?->diffForHumans(),
        ];
    }
}
