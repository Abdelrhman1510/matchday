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

        // can_book: match must be published, upcoming, within booking window,
        // have seats available, and user must not already have an active booking
        $now = now();
        $withinWindow = true;
        if ($this->booking_opens_at && $now->lt($this->booking_opens_at)) {
            $withinWindow = false;
        }
        if ($this->booking_closes_at && $now->gt($this->booking_closes_at)) {
            $withinWindow = false;
        }

        $canBook = $this->is_published
            && $this->status === 'upcoming'
            && $withinWindow
            && $this->seats_available > 0
            && !$isBooked;

        return [
            'id' => $this->id,
            'league' => $this->league,
            'league_ar' => $this->league_ar,
            'match_date' => $this->match_date?->format('Y-m-d'),
            'kick_off' => $this->kick_off,
            'status' => $this->status,
            'is_booked' => $isBooked,
            'can_book' => $canBook,
            'is_saved' => $isSaved,
            'home_team' => $this->when($this->relationLoaded('homeTeam'), function () {
                return [
                    'id' => $this->homeTeam->id,
                    'name' => $this->homeTeam->name,
                    'name_ar' => $this->homeTeam->name_ar,
                    'short_name' => $this->homeTeam->short_name,
                    'logo' => $this->teamLogo($this->homeTeam),
                ];
            }),
            'away_team' => $this->when($this->relationLoaded('awayTeam'), function () {
                return [
                    'id' => $this->awayTeam->id,
                    'name' => $this->awayTeam->name,
                    'name_ar' => $this->awayTeam->name_ar,
                    'short_name' => $this->awayTeam->short_name,
                    'logo' => $this->teamLogo($this->awayTeam),
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

    /** Always a loadable URL: a team with no logo gets the same placeholder other teams already use. */
    private function teamLogo($team): string
    {
        if (!$team->logo) {
            return 'https://ui-avatars.com/api/?' . http_build_query([
                'name' => $team->short_name ?: $team->name,
                'background' => '1a1a2e',
                'color' => 'fff',
                'size' => 200,
                'bold' => 'true',
                'format' => 'png',
            ]);
        }

        return str_starts_with($team->logo, 'http') ? $team->logo : url('storage/' . $team->logo);
    }
}
