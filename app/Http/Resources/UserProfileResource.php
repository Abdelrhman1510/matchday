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
        // Format avatar with multi-size URLs
        $avatar = null;
        if ($this->avatar && is_array($this->avatar)) {
            $avatar = [
                'original' => url('storage/' . $this->avatar['original']),
                'medium' => url('storage/' . $this->avatar['medium']),
                'thumbnail' => url('storage/' . $this->avatar['thumbnail']),
            ];
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
