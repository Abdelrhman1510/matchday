<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if user has a booking for this match
        $isBooked = false;
        if ($request->user()) {
            $isBooked = $request->user()->bookings()
                ->where('match_id', $this->id)
                ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                ->exists();
        }

        // Check if match is saved by authenticated user
        $isSaved = false;
        if ($request->user()) {
            $isSaved = $request->user()->savedMatches()->where('match_id', $this->id)->exists();
        }

        return [
            'id' => $this->id,
            'league' => $this->league,
            'match_date' => $this->match_date?->format('Y-m-d'),
            'kick_off' => $this->kick_off,
            'status' => $this->status,
            'is_booked' => $isBooked,
            'is_saved' => $isSaved,
            'home_team' => $this->when($this->relationLoaded('homeTeam'), function () {
                return [
                    'id' => $this->homeTeam->id,
                    'name' => $this->homeTeam->name,
                    'short_name' => $this->homeTeam->short_name,
                    'logo' => $this->homeTeam->logo ? url('storage/' . $this->homeTeam->logo) : null,
                ];
            }),
            'away_team' => $this->when($this->relationLoaded('awayTeam'), function () {
                return [
                    'id' => $this->awayTeam->id,
                    'name' => $this->awayTeam->name,
                    'short_name' => $this->awayTeam->short_name,
                    'logo' => $this->awayTeam->logo ? url('storage/' . $this->awayTeam->logo) : null,
                ];
            }),
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'seats_available' => $this->seats_available,
            'price_per_seat' => (float) $this->price_per_seat,
            'branch' => $this->when($this->relationLoaded('branch'), function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'cafe' => $this->when($this->branch->relationLoaded('cafe'), function () {
                        return [
                            'id' => $this->branch->cafe->id,
                            'name' => $this->branch->cafe->name,
                        ];
                    }),
                ];
            }),
        ];
    }
}
