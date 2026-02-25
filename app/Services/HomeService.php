<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\GameMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeService
{
    /**
     * Get aggregated home feed data for authenticated user
     */
    public function getHomeFeed($user, ?float $lat = null, ?float $lng = null): array
    {
        $cacheKey = "home_feed_user_{$user->id}_" . ($lat ? "{$lat}_{$lng}" : 'no_location');
        
        return Cache::remember($cacheKey, 300, function () use ($user, $lat, $lng) {
            return [
                'user' => $this->getUserSummary($user),
                'upcoming_booking' => $this->getUpcomingBooking($user),
                'nearby_cafes' => $lat && $lng ? $this->getNearbyCafes($lat, $lng, $user, 3) : [],
                'featured_cafes' => $this->getFeaturedCafes($user, 3),
                'live_matches' => $this->getLiveMatches(3),
                'upcoming_matches' => $this->getUpcomingMatches($user, 5),
                'popular_this_week' => $this->getPopularMatches(3),
            ];
        });
    }

    private function getUserSummary($user): array
    {
        $unreadCount = $user->notifications()
            ->whereNull('read_at')
            ->count();

        // Get tier from loyalty card, default to 'bronze' if no card exists
        $tier = $user->loyaltyCard?->tier ?? 'bronze';

        return [
            'name' => $user->name,
            'tier' => $tier,
            'unread_notifications_count' => $unreadCount,
        ];
    }

    private function getUpcomingBooking($user): ?array
    {
        $booking = Booking::where('bookings.user_id', $user->id)
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->with([
                'match.homeTeam',
                'match.awayTeam',
                'match.branch.cafe',
            ])
            ->join('matches', 'bookings.match_id', '=', 'matches.id')
            ->where('matches.match_date', '>=', now()->toDateString())
            ->orderBy('matches.match_date')
            ->orderBy('matches.kick_off')
            ->select('bookings.*')
            ->first();

        if (!$booking) {
            return null;
        }

        $match = $booking->match;
        $branch = $match->branch;

        return [
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'status' => $booking->status,
            'guests_count' => $booking->guests_count,
            'match' => [
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'match_date' => $match->match_date->format('Y-m-d'),
                'kick_off' => $match->kick_off,
                'league' => $match->league,
            ],
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'cafe_name' => $branch->cafe->name,
            ],
        ];
    }

    private function getNearbyCafes(float $lat, float $lng, $user, int $limit): array
    {
        $savedCafeIds = $user->savedCafes()->pluck('cafes.id')->toArray();

        $cafes = Cafe::select('cafes.*')
            ->join('branches', 'cafes.id', '=', 'branches.cafe_id')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, branches.address, 
                branches.latitude, branches.longitude,
                (6371 * acos(cos(radians(?)) * cos(radians(branches.latitude)) * 
                cos(radians(branches.longitude) - radians(?)) + sin(radians(?)) * 
                sin(radians(branches.latitude)))) AS distance_km', [$lat, $lng, $lat])
            ->where('branches.is_open', true)
            ->having('distance_km', '<', 10)
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        return $cafes->map(function ($cafe) use ($savedCafeIds) {
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo,
                'branch_id' => $cafe->branch_id,
                'branch_name' => $cafe->branch_name,
                'address' => $cafe->address,
                'rating' => (float) $cafe->avg_rating,
                'distance_km' => round($cafe->distance_km, 1),
                'is_saved' => in_array($cafe->id, $savedCafeIds),
                'is_premium' => $cafe->is_premium,
            ];
        })->toArray();
    }

    private function getFeaturedCafes($user, int $limit): array
    {
        $savedCafeIds = $user->savedCafes()->pluck('cafes.id')->toArray();

        $cafes = Cafe::with(['branches' => function ($query) {
                $query->where('is_open', true)->limit(1);
            }])
            ->premium()
            ->highRated(4.0)
            ->orderBy('avg_rating', 'desc')
            ->limit($limit)
            ->get();

        return $cafes->map(function ($cafe) use ($savedCafeIds) {
            $branch = $cafe->branches->first();
            
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo,
                'description' => $cafe->description,
                'rating' => (float) $cafe->avg_rating,
                'total_reviews' => $cafe->total_reviews,
                'city' => $cafe->city,
                'is_saved' => in_array($cafe->id, $savedCafeIds),
                'is_premium' => true,
                'branch_count' => $cafe->branches->count(),
            ];
        })->toArray();
    }

    private function getLiveMatches(int $limit): array
    {
        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->live()
            ->published()
            ->orderBy('kick_off')
            ->limit($limit)
            ->get();

        return $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'status' => 'live',
                'league' => $match->league,
                'kick_off' => $match->kick_off,
                'cafe_name' => $match->branch->cafe->name,
                'branch_name' => $match->branch->name,
            ];
        })->toArray();
    }

    private function getUpcomingMatches($user, int $limit): array
    {
        $userBookedMatchIds = Booking::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('match_id')
            ->toArray();

        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->upcoming()
            ->published()
            ->limit($limit)
            ->get();

        return $matches->map(function ($match) use ($userBookedMatchIds) {
            return [
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'match_date' => $match->match_date->format('Y-m-d'),
                'kick_off' => $match->kick_off,
                'league' => $match->league,
                'status' => $match->status,
                'price_per_seat' => (float) $match->price_per_seat,
                'seats_available' => $match->seats_available,
                'cafe_name' => $match->branch->cafe->name,
                'branch_name' => $match->branch->name,
                'is_booked' => in_array($match->id, $userBookedMatchIds),
            ];
        })->toArray();
    }

    private function getPopularMatches(int $limit): array
    {
        $weekAgo = now()->subWeek();

        $popularMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->select('matches.*')
            ->leftJoin('bookings', 'matches.id', '=', 'bookings.match_id')
            ->where('bookings.created_at', '>=', $weekAgo)
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->groupBy('matches.id')
            ->selectRaw('COUNT(bookings.id) as booking_count')
            ->orderBy('booking_count', 'desc')
            ->published()
            ->where('matches.match_date', '>=', now()->toDateString())
            ->limit($limit)
            ->get();

        return $popularMatches->map(function ($match) {
            return [
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'match_date' => $match->match_date->format('Y-m-d'),
                'kick_off' => $match->kick_off,
                'league' => $match->league,
                'cafe_name' => $match->branch->cafe->name,
                'booking_count' => $match->booking_count ?? 0,
            ];
        })->toArray();
    }
}
