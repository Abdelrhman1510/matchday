<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchDetailResource extends JsonResource
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
            $userId = $request->user()->id;
            $matchId = $this->id;
            
            // Debug logging
            \Log::info("Checking is_booked for user {$userId} and match {$matchId}");
            
            $isBooked = $request->user()->bookings()
                ->where('match_id', $this->id)
                ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                ->exists();
                
            \Log::info("is_booked result: " . ($isBooked ? 'true' : 'false'));
        } else {
            \Log::info("No authenticated user in MatchDetailResource");
        }

        return [
            'id' => $this->id,
            'league' => $this->league,
            'match_date' => $this->match_date?->format('Y-m-d'),
            'kick_off' => $this->kick_off,
            'status' => $this->status,
            'is_booked' => $isBooked,
            'duration_minutes' => $this->duration_minutes,
            'home_team' => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'short_name' => $this->homeTeam->short_name,
                'logo' => $this->homeTeam->logo ? url('storage/' . $this->homeTeam->logo) : null,
            ],
            'away_team' => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'short_name' => $this->awayTeam->short_name,
                'logo' => $this->awayTeam->logo ? url('storage/' . $this->awayTeam->logo) : null,
            ],
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'address' => $this->branch->address,
                'total_seats' => $this->branch->total_seats,
                'cafe' => [
                    'id' => $this->branch->cafe->id,
                    'name' => $this->branch->cafe->name,
                    'logo' => is_array($this->branch->cafe->logo) 
                        ? $this->branch->cafe->logo 
                        : ($this->branch->cafe->logo ? url('storage/' . $this->branch->cafe->logo) : null),
                ],
            ],
            'seats_available' => $this->seats_available,
            'price_per_seat' => (float) $this->price_per_seat,
            'booking_opens_at' => $this->booking_opens_at?->format('Y-m-d H:i:s'),
            'booking_closes_at' => $this->booking_closes_at?->format('Y-m-d H:i:s'),
            'booking_stats' => [
                'total_bookings' => $this->total_bookings ?? 0,
                'total_seats_booked' => $this->total_seats_booked ?? 0,
                'revenue' => (float) ($this->revenue ?? 0),
                'occupancy_rate' => $this->branch->total_seats > 0 
                    ? round((($this->total_seats_booked ?? 0) / $this->branch->total_seats) * 100, 2) 
                    : 0,
            ],
            'seating_sections' => $this->when($this->relationLoaded('branch'), function () {
                if ($this->branch->relationLoaded('seatingSections')) {
                    return $this->branch->seatingSections->map(function ($section) {
                        $availableSeats = $section->seats->where('is_available', true)->count();
                        
                        return [
                            'id' => $section->id,
                            'name' => $section->name,
                            'type' => $section->type,
                            'total_seats' => $section->total_seats,
                            'available_seats' => $availableSeats,
                            'extra_cost' => (float) $section->extra_cost,
                            'icon' => $section->icon,
                        ];
                    });
                }
                return null;
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
