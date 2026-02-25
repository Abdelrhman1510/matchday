<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
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
            'type' => $this->type,
            'is_active' => $this->is_active,
            'viewers_count' => $this->viewers_count,
            'match' => [
                'id' => $this->match->id,
                'league' => $this->match->league,
                'match_date' => $this->match->match_date->format('Y-m-d'),
                'kick_off' => $this->match->kick_off,
                'status' => $this->match->status,
                'home_team' => [
                    'id' => $this->match->homeTeam->id,
                    'name' => $this->match->homeTeam->name,
                    'logo' => $this->match->homeTeam->logo,
                ],
                'away_team' => [
                    'id' => $this->match->awayTeam->id,
                    'name' => $this->match->awayTeam->name,
                    'logo' => $this->match->awayTeam->logo,
                ],
                'home_score' => $this->match->home_score,
                'away_score' => $this->match->away_score,
            ],
            'branch' => $this->when($this->branch_id !== null && $this->branch, function() {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'address' => $this->branch->address,
                    'cafe' => [
                        'id' => $this->branch->cafe->id,
                        'name' => $this->branch->cafe->name,
                        'logo' => $this->branch->cafe->logo,
                    ],
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            
            // UI helper flags
            'is_public' => $this->type === 'public' && $this->branch_id === null,
            'is_cafe_room' => $this->type === 'cafe' && $this->branch_id !== null,
            'can_send_messages' => $this->is_active,
        ];
    }
}
