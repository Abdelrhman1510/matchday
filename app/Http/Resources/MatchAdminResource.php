<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bookingCount = $this->booking_count ?? 0;

        return [
            'id' => $this->id,
            'league' => $this->league,
            'match_date' => $this->match_date?->format('Y-m-d'),
            'kick_off' => $this->kick_off,
            'status' => $this->status,
            'is_published' => $this->is_published,

            'home_team' => $this->when($this->relationLoaded('homeTeam'), fn() => [
                'id' => $this->homeTeam->id,
                'name' => $this->homeTeam->name,
                'short_name' => $this->homeTeam->short_name,
                'logo' => $this->formatTeamLogo($this->homeTeam->logo),
            ]),
            'away_team' => $this->when($this->relationLoaded('awayTeam'), fn() => [
                'id' => $this->awayTeam->id,
                'name' => $this->awayTeam->name,
                'short_name' => $this->awayTeam->short_name,
                'logo' => $this->formatTeamLogo($this->awayTeam->logo),
            ]),

            'home_score' => $this->home_score,
            'away_score' => $this->away_score,

            'branch' => $this->when($this->relationLoaded('branch'), fn() => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'cafe' => $this->when($this->branch->relationLoaded('cafe'), fn() => [
                    'id' => $this->branch->cafe->id,
                    'name' => $this->branch->cafe->name,
                    'logo' => is_array($this->branch->cafe->logo)
                        ? $this->branch->cafe->logo
                        : ($this->branch->cafe->logo ? url('storage/' . $this->branch->cafe->logo) : null),
                ]),
            ]),

            'seats_available' => $this->seats_available,
            'price_per_seat' => (float) $this->price_per_seat,
            'ticket_price' => (float) ($this->ticket_price ?? $this->price_per_seat),
            'duration_minutes' => $this->duration_minutes,
            'total_revenue' => (float) ($this->revenue ?? $this->total_revenue ?? 0),
            'field_name' => $this->field_name,
            'venue_name' => $this->venue_name,

            'booking_count' => $bookingCount,
            'booking_opens_at' => $this->booking_opens_at?->toIso8601String(),
            'booking_closes_at' => $this->booking_closes_at?->toIso8601String(),

            // UI Flags
            'can_edit' => $this->status === 'upcoming',
            'can_publish' => !$this->is_published && $this->status === 'upcoming',
            'can_cancel' => in_array($this->status, ['upcoming', 'live']),
            'can_update_score' => in_array($this->status, ['live', 'finished']),
            'can_go_live' => $this->status === 'upcoming',
            'is_upcoming' => $this->status === 'upcoming',
            'is_live' => $this->status === 'live',
            'is_finished' => $this->status === 'finished',
            'is_cancelled' => $this->status === 'cancelled',

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format team logo to multi-size if it's an array, else single URL
     */
    protected function formatTeamLogo($logo): array|string|null
    {
        if (!$logo) {
            return null;
        }

        if (is_array($logo)) {
            return $logo;
        }

        $url = url('storage/' . $logo);
        return [
            'original' => $url,
            'medium' => $url,
            'thumbnail' => $url,
        ];
    }
}
