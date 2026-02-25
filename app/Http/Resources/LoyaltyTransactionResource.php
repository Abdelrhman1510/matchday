<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points' => $this->points,
            'type' => $this->type,
            'description' => $this->description,
            'booking' => $this->when($this->booking_id, function () {
                if ($this->relationLoaded('booking')) {
                    return [
                        'id' => $this->booking->id,
                        'booking_code' => $this->booking->booking_code,
                        'match' => $this->booking->relationLoaded('match') ? [
                            'home_team' => $this->booking->match->homeTeam->name,
                            'away_team' => $this->booking->match->awayTeam->name,
                            'match_date' => $this->booking->match->match_date?->format('Y-m-d'),
                        ] : null,
                    ];
                }
                return ['id' => $this->booking_id];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'date_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
