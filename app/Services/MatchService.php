<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MatchService
{
    /**
     * Get matches with filters and pagination
     */
    public function getMatches(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = GameMatch::query()
            ->published()
            ->with(['homeTeam', 'awayTeam', 'branch.cafe']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Filter by date
        if (!empty($filters['date'])) {
            $query->forDate($filters['date']);
        }

        // Filter by team_id (either home or away)
        if (!empty($filters['team_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('home_team_id', $filters['team_id'])
                  ->orWhere('away_team_id', $filters['team_id']);
            });
        }

        // Filter by league
        if (!empty($filters['league'])) {
            $query->byLeague($filters['league']);
        }

        // Filter by branch_id
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->orderBy('match_date', 'desc')
            ->orderBy('kick_off', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get live matches with scores
     */
    public function getLiveMatches(): \Illuminate\Database\Eloquent\Collection
    {
        return GameMatch::query()
            ->published()
            ->live()
            ->with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->orderBy('kick_off')
            ->get();
    }

    /**
     * Get upcoming matches
     */
    public function getUpcomingMatches(int $perPage = 15): LengthAwarePaginator
    {
        return GameMatch::query()
            ->published()
            ->upcoming()
            ->with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->paginate($perPage);
    }

    /**
     * Get popular matches (most booked this week)
     */
    public function getPopularMatches(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $oneWeekAgo = now()->subWeek();

        return GameMatch::query()
            ->published()
            ->where('match_date', '>=', now()->toDateString())
            ->withCount(['bookings' => function ($query) use ($oneWeekAgo) {
                $query->where('created_at', '>=', $oneWeekAgo)
                      ->whereIn('status', ['confirmed', 'checked_in']);
            }])
            ->with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get match detail by ID
     */
    public function getMatchById(int $id): ?GameMatch
    {
        $match = GameMatch::query()
            ->published()
            ->with([
                'homeTeam',
                'awayTeam',
                'branch.cafe',
                'branch.seatingSections' => function ($query) {
                    $query->with('seats');
                }
            ])
            ->find($id);

        if (!$match) {
            return null;
        }

        // Add booking statistics
        $match->total_bookings = $match->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        $match->total_seats_booked = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->where('bookings.match_id', $match->id)
            ->whereIn('bookings.status', ['confirmed', 'checked_in'])
            ->count();

        $match->revenue = $match->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->sum('total_amount');

        return $match;
    }

    /**
     * Get seating map for a match
     */
    public function getSeatingMap(int $matchId, int $branchId): ?array
    {
        $match = GameMatch::query()
            ->published()
            ->where('branch_id', $branchId)
            ->with([
                'branch.seatingSections.seats'
            ])
            ->find($matchId);

        if (!$match) {
            return null;
        }

        // Get all booked seat IDs for this match
        $bookedSeatIds = DB::table('booking_seats')
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
            ->where('bookings.match_id', $matchId)
            ->whereIn('bookings.status', ['confirmed', 'checked_in'])
            ->pluck('booking_seats.seat_id')
            ->toArray();

        // Build seating map
        $sections = $match->branch->seatingSections->map(function ($section) use ($bookedSeatIds) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'type' => $section->type,
                'total_seats' => $section->total_seats,
                'extra_cost' => (float) $section->extra_cost,
                'icon' => $section->icon,
                'seats' => $section->seats->map(function ($seat) use ($bookedSeatIds) {
                    return [
                        'id' => $seat->id,
                        'label' => $seat->label,
                        'is_available' => $seat->is_available,
                        'is_booked_for_this_match' => in_array($seat->id, $bookedSeatIds),
                    ];
                }),
            ];
        });

        return [
            'match_id' => $match->id,
            'match_info' => [
                'home_team' => [
                    'id' => $match->homeTeam->id,
                    'name' => $match->homeTeam->name,
                    'short_name' => $match->homeTeam->short_name,
                    'logo' => $match->homeTeam->logo ? url('storage/' . $match->homeTeam->logo) : null,
                ],
                'away_team' => [
                    'id' => $match->awayTeam->id,
                    'name' => $match->awayTeam->name,
                    'short_name' => $match->awayTeam->short_name,
                    'logo' => $match->awayTeam->logo ? url('storage/' . $match->awayTeam->logo) : null,
                ],
                'match_date' => $match->match_date->format('Y-m-d'),
                'kick_off' => $match->kick_off,
                'status' => $match->status,
            ],
            'branch' => [
                'id' => $match->branch->id,
                'name' => $match->branch->name,
                'total_seats' => $match->branch->total_seats,
            ],
            'sections' => $sections,
            'seats_available' => $match->seats_available,
            'price_per_seat' => (float) $match->price_per_seat,
        ];
    }
}
