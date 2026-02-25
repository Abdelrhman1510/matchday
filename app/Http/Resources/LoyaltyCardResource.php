<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'card_number' => $this->card_number,
            'points' => $this->points,
            'tier' => $this->tier,
            'total_points_earned' => $this->total_points_earned,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'member_since' => $this->issued_date?->diffForHumans(),
        ];
    }
}
