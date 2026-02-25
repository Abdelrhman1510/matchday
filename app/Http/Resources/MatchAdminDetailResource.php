<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchAdminDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $match = $this['match'];

        return [
            'id' => $match->id,
            'league' => $match->league,
            'match_date' => $match->match_date?->format('Y-m-d'),
            'kick_off' => $match->kick_off,
            'status' => $match->status,
            'is_published' => $match->is_published,

            'home_team' => [
                'id' => $match->homeTeam->id,
                'name' => $match->homeTeam->name,
                'short_name' => $match->homeTeam->short_name,
                'logo' => $this->formatTeamLogo($match->homeTeam->logo),
            ],
            'away_team' => [
                'id' => $match->awayTeam->id,
                'name' => $match->awayTeam->name,
                'short_name' => $match->awayTeam->short_name,
                'logo' => $this->formatTeamLogo($match->awayTeam->logo),
            ],

            'home_score' => $match->home_score,
            'away_score' => $match->away_score,

            'branch' => [
                'id' => $match->branch->id,
                'name' => $match->branch->name,
                'cafe' => [
                    'id' => $match->branch->cafe->id,
                    'name' => $match->branch->cafe->name,
                    'logo' => is_array($match->branch->cafe->logo)
                        ? $match->branch->cafe->logo
                        : ($match->branch->cafe->logo ? url('storage/' . $match->branch->cafe->logo) : null),
                ],
            ],

            'seats_available' => $match->seats_available,
            'price_per_seat' => (float) $match->price_per_seat,
            'duration_minutes' => $match->duration_minutes,
            'field_name' => $match->field_name,
            'venue_name' => $match->venue_name,

            // Booking stats
            'booking_stats' => $this['booking_stats'],
            'revenue' => $this['revenue'],
            'booking_timing' => $this['booking_timing'],

            // UI Flags
            'can_edit' => $match->status === 'upcoming',
            'can_publish' => !$match->is_published && $match->status === 'upcoming',
            'can_cancel' => in_array($match->status, ['upcoming', 'live']),
            'can_update_score' => in_array($match->status, ['live', 'finished']),
            'can_go_live' => $match->status === 'upcoming',
            'can_send_reminder' => !($match->last_reminder_sent_at && $match->last_reminder_sent_at->isToday()),
            'last_reminder_sent_at' => $match->last_reminder_sent_at?->toIso8601String(),
            'is_upcoming' => $match->status === 'upcoming',
            'is_live' => $match->status === 'live',
            'is_finished' => $match->status === 'finished',
            'is_cancelled' => $match->status === 'cancelled',

            'created_at' => $match->created_at?->toIso8601String(),
            'updated_at' => $match->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Format team logo to multi-size format
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
