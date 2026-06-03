<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Format avatar with multi-size URLs. Handles uploaded (original/medium/
        // thumbnail paths) and external (Google) avatars stored as ['url' => ...].
        $avatar = null;
        if ($this->avatar && is_array($this->avatar)) {
            if (isset($this->avatar['url'])) {
                $avatar = [
                    'original' => $this->avatar['url'],
                    'medium' => $this->avatar['url'],
                    'thumbnail' => $this->avatar['url'],
                ];
            } elseif (isset($this->avatar['original'])) {
                $avatar = [
                    'original' => url('storage/' . $this->avatar['original']),
                    'medium' => url('storage/' . ($this->avatar['medium'] ?? $this->avatar['original'])),
                    'thumbnail' => url('storage/' . ($this->avatar['thumbnail'] ?? $this->avatar['original'])),
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $avatar,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'fan_profile' => $this->whenLoaded('fanProfile', function () {
                return [
                    'favorite_team_id' => $this->fanProfile->favorite_team_id,
                    'member_since' => $this->fanProfile->member_since,
                ];
            }),
            'loyalty_card' => $this->whenLoaded('loyaltyCard', function () {
                return [
                    'card_number' => $this->loyaltyCard->card_number,
                    'points' => $this->loyaltyCard->points,
                    'tier' => $this->loyaltyCard->tier,
                    'issued_date' => $this->loyaltyCard->issued_date,
                ];
            }),
        ];
    }
}
