<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $matchDate = $this->match->match_date;
        $matchStatus = $this->match->status;
        $bookingStatus = $this->status;

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $bookingStatus,
            'guests_count' => $this->guests_count,
            'total_amount' => (float) $this->total_amount,
            'total_price' => (float) $this->total_amount,
            'currency' => $this->currency,
            'match' => [
                'id' => $this->match->id,
                'league' => $this->match->league,
                'match_date' => $matchDate->format('Y-m-d'),
                'kick_off' => $this->match->kick_off,
                'status' => $matchStatus,
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
            'branch' => [
                'id' => $this->match->branch->id,
                'name' => $this->match->branch->name,
                'address' => $this->match->branch->address,
                'cafe' => [
                    'id' => $this->match->branch->cafe->id,
                    'name' => $this->match->branch->cafe->name,
                    'logo' => $this->match->branch->cafe->logo,
                ],
            ],
            'seats_count' => $this->seats->count(),
            'payment_status' => $this->payment?->status ?? 'pending',
            'created_at' => $this->created_at->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            
            // UI helper flags
            'can_cancel' => in_array($matchStatus, ['upcoming', 'scheduled']) 
                && in_array($bookingStatus, ['confirmed', 'pending']),
            'can_rebook' => $matchStatus === 'finished',
            'can_enter_fan_room' => $matchStatus === 'live' 
                && in_array($bookingStatus, ['confirmed', 'checked_in']),
            'is_today' => $matchDate->isToday(),
        ];
    }
}
