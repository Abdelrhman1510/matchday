<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BranchHour;
use App\Models\Cafe;
use App\Models\GameMatch;
use Illuminate\Support\Facades\Cache;

class ExploreService
{
    /**
     * Get aggregated explore data
     * Public endpoint, enhanced if authenticated and with location
     */
    public function getExploreData($user = null, ?float $lat = null, ?float $lng = null): array
    {
        // User-specific flags are always computed fresh — never cached
        $savedCafeIds = $user ? $user->savedCafes()->pluck('cafes.id')->toArray() : [];
        $userBookedMatchIds = $user ? Booking::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('match_id')
            ->toArray() : [];

        $cacheKey = 'explore_v2_public_' . ($lat ? "{$lat}_{$lng}" : 'no_location');

        $data = Cache::remember($cacheKey, 300, function () use ($lat, $lng) {
            return [
                'featured_cafes' => $this->getFeaturedCafes([], $lat, $lng, 5),
                'nearby_cafes' => ($lat && $lng) ? $this->getNearbyCafes($lat, $lng, [], 5) : [],
                'trending_cafes' => $this->getTrendingCafes([], 5),
                'matches_today' => $this->getMatchesToday([]),
                'popular_matches' => $this->getPopularMatches([], 5),
            ];
        });

        // Overlay per-user flags on the cached structural data
        foreach ($data['featured_cafes'] as &$cafe) {
            $cafe['is_saved'] = in_array($cafe['id'], $savedCafeIds);
        }
        foreach ($data['nearby_cafes'] as &$cafe) {
            $cafe['is_saved'] = in_array($cafe['id'], $savedCafeIds);
        }
        foreach ($data['trending_cafes'] as &$cafe) {
            $cafe['is_saved'] = in_array($cafe['id'], $savedCafeIds);
        }
        foreach ($data['matches_today'] as &$match) {
            $match['is_booked'] = in_array($match['id'], $userBookedMatchIds);
        }
        foreach ($data['popular_matches'] as &$match) {
            $match['is_booked'] = in_array($match['id'], $userBookedMatchIds);
        }

        return $data;
    }

    private function getFeaturedCafes(array $savedCafeIds, ?float $lat, ?float $lng, int $limit): array
    {
        $cafes = Cafe::with(['branches' => function ($query) {
                $query->where('is_open', true);
            }])
            ->withActiveSubscription()
            ->premium()
            ->highRated(4.0)
            ->orderBy('avg_rating', 'desc')
            ->limit($limit)
            ->get();

        return $cafes->map(function ($cafe) use ($savedCafeIds, $lat, $lng) {
            $isOpenNow = $this->isCafeOpenNow($cafe);
            
            $data = [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo ? (str_starts_with($cafe->logo, 'http') ? $cafe->logo : url('storage/' . $cafe->logo)) : null,
                'description' => $cafe->description,
                'city' => $cafe->city,
                'rating' => (float) $cafe->avg_rating,
                'total_reviews' => $cafe->total_reviews,
                'is_saved' => in_array($cafe->id, $savedCafeIds),
                'is_open_now' => $isOpenNow,
                'is_premium' => true,
            ];

            // Calculate distance if coordinates provided
            if ($lat && $lng && $cafe->branches->count() > 0) {
                $nearestBranch = $cafe->branches->sortBy(function ($branch) use ($lat, $lng) {
                    return $this->calculateDistance($lat, $lng, $branch->latitude, $branch->longitude);
                })->first();
                
                if ($nearestBranch) {
                    $data['distance_km'] = round($this->calculateDistance($lat, $lng, $nearestBranch->latitude, $nearestBranch->longitude), 1);
                }
            }

            return $data;
        })->toArray();
    }

    private function getNearbyCafes(float $lat, float $lng, array $savedCafeIds, int $limit): array
    {
        $cafes = Cafe::select('cafes.*')
            ->join('branches', 'cafes.id', '=', 'branches.cafe_id')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, branches.address,
                branches.latitude, branches.longitude,
                (6371 * acos(cos(radians(?)) * cos(radians(branches.latitude)) *
                cos(radians(branches.longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(branches.latitude)))) AS distance_km', [$lat, $lng, $lat])
            ->withActiveSubscription()
            ->where('branches.is_open', true)
            ->having('distance_km', '<', 20)
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        return $cafes->map(function ($cafe) use ($savedCafeIds) {
            $isOpenNow = $this->isBranchOpenNow($cafe->branch_id);
            
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo ? (str_starts_with($cafe->logo, 'http') ? $cafe->logo : url('storage/' . $cafe->logo)) : null,
                'branch_id' => $cafe->branch_id,
                'branch_name' => $cafe->branch_name,
                'address' => $cafe->address,
                'rating' => (float) $cafe->avg_rating,
                'distance_km' => round($cafe->distance_km, 1),
                'is_saved' => in_array($cafe->id, $savedCafeIds),
                'is_open_now' => $isOpenNow,
                'is_premium' => $cafe->is_premium,
            ];
        })->toArray();
    }

    private function getTrendingCafes(array $savedCafeIds, int $limit): array
    {
        // Cache trending cafes for 10 minutes
        $cacheKey = 'trending_cafes_' . $limit;
        
        $cafes = Cache::remember($cacheKey, 600, function () use ($limit) {
            $weekAgo = now()->subWeek();

            return Cafe::select('cafes.*')
                ->join('branches', 'cafes.id', '=', 'branches.cafe_id')
                ->join('bookings', 'branches.id', '=', 'bookings.branch_id')
                ->withActiveSubscription()
                ->where('bookings.created_at', '>=', $weekAgo)
                ->whereIn('bookings.status', ['confirmed', 'pending', 'completed'])
                ->groupBy('cafes.id')
                ->selectRaw('COUNT(bookings.id) as booking_count')
                ->orderBy('booking_count', 'desc')
                ->limit($limit)
                ->get();
        });

        return $cafes->map(function ($cafe) use ($savedCafeIds) {
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo ? (str_starts_with($cafe->logo, 'http') ? $cafe->logo : url('storage/' . $cafe->logo)) : null,
                'city' => $cafe->city,
                'rating' => (float) $cafe->avg_rating,
                'booking_count' => $cafe->booking_count ?? 0,
                'is_saved' => in_array($cafe->id, $savedCafeIds),
                'is_premium' => $cafe->is_premium,
            ];
        })->toArray();
    }

    private function getMatchesToday(array $userBookedMatchIds): array
    {
        $today = now()->toDateString();

        // Get live matches
        $liveMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->live()
            ->published()
            ->where('match_date', $today)
            ->orderBy('kick_off')
            ->get();

        // Get upcoming matches today
        $upcomingMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->where('status', 'upcoming')
            ->published()
            ->where('match_date', $today)
            ->orderBy('kick_off')
            ->get();

        $allMatches = $liveMatches->merge($upcomingMatches);

        return $allMatches->map(function ($match) use ($userBookedMatchIds) {
            $data = [
                'id'              => $match->id,
                'home_team'       => $match->homeTeam->name,
                'home_team_short' => $match->homeTeam->short_name,
                'home_team_logo'  => $match->homeTeam->logo ? (str_starts_with($match->homeTeam->logo, 'http') ? $match->homeTeam->logo : url('storage/' . $match->homeTeam->logo)) : null,
                'away_team'       => $match->awayTeam->name,
                'away_team_short' => $match->awayTeam->short_name,
                'away_team_logo'  => $match->awayTeam->logo ? (str_starts_with($match->awayTeam->logo, 'http') ? $match->awayTeam->logo : url('storage/' . $match->awayTeam->logo)) : null,
                'kick_off'        => $match->kick_off,
                'league'          => $match->league,
                'status'          => $match->status,
                'cafe_name'       => $match->branch->cafe->name,
                'branch_name'     => $match->branch->name,
                'price_per_seat'  => (float) $match->price_per_seat,
                'seats_available' => $match->seats_available,
                'is_booked'       => in_array($match->id, $userBookedMatchIds),
            ];

            // Add scores for live matches
            if ($match->status === 'live') {
                $data['home_score'] = $match->home_score;
                $data['away_score'] = $match->away_score;
            }

            return $data;
        })->toArray();
    }

    private function getPopularMatches(array $userBookedMatchIds, int $limit): array
    {
        $weekAgo = now()->subWeek();

        $popularMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->select('game_matches.*')
            ->leftJoin('bookings', 'game_matches.id', '=', 'bookings.match_id')
            ->where('bookings.created_at', '>=', $weekAgo)
            ->whereIn('bookings.status', ['confirmed', 'pending', 'completed'])
            ->where('game_matches.match_date', '>=', now()->toDateString())
            ->groupBy('game_matches.id')
            ->selectRaw('COUNT(bookings.id) as booking_count')
            ->orderBy('booking_count', 'desc')
            ->published()
            ->limit($limit)
            ->get();

        return $popularMatches->map(function ($match) use ($userBookedMatchIds) {
            return [
                'id'              => $match->id,
                'home_team'       => $match->homeTeam->name,
                'home_team_short' => $match->homeTeam->short_name,
                'home_team_logo'  => $match->homeTeam->logo ? (str_starts_with($match->homeTeam->logo, 'http') ? $match->homeTeam->logo : url('storage/' . $match->homeTeam->logo)) : null,
                'away_team'       => $match->awayTeam->name,
                'away_team_short' => $match->awayTeam->short_name,
                'away_team_logo'  => $match->awayTeam->logo ? (str_starts_with($match->awayTeam->logo, 'http') ? $match->awayTeam->logo : url('storage/' . $match->awayTeam->logo)) : null,
                'match_date'      => $match->match_date->format('Y-m-d'),
                'kick_off'        => $match->kick_off,
                'league'          => $match->league,
                'status'          => $match->status,
                'cafe_name'       => $match->branch->cafe->name,
                'price_per_seat'  => (float) $match->price_per_seat,
                'booking_count'   => $match->booking_count ?? 0,
                'is_booked'       => in_array($match->id, $userBookedMatchIds),
            ];
        })->toArray();
    }

    private function isCafeOpenNow(Cafe $cafe): bool
    {
        // Check if any branch is open now
        $openBranches = $cafe->branches->filter(function ($branch) {
            return $this->isBranchOpenNow($branch->id);
        });

        return $openBranches->count() > 0;
    }

    private function isBranchOpenNow(int $branchId): bool
    {
        $dayOfWeek = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $currentTime = now()->format('H:i:s');

        $hours = BranchHour::where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_open', true)
            ->where('open_time', '<=', $currentTime)
            ->where('close_time', '>=', $currentTime)
            ->exists();

        return $hours;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // kilometers

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
