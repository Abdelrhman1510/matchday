<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExploreService
{
    /**
     * Get aggregated explore data
     * Public endpoint, enhanced if authenticated and with location
     */
    public function getExploreData($user = null, ?float $lat = null, ?float $lng = null): array
    {
        $cacheKey = 'explore_data_' . ($user ? "user_{$user->id}_" : 'public_') . ($lat ? "{$lat}_{$lng}" : 'no_location');
        
        return Cache::remember($cacheKey, 300, function () use ($user, $lat, $lng) {
            $savedCafeIds = $user ? $user->savedCafes()->pluck('cafes.id')->toArray() : [];
            $userBookedMatchIds = $user ? Booking::where('user_id', $user->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->pluck('match_id')
                ->toArray() : [];

            return [
                'featured_cafes' => $this->getFeaturedCafes($savedCafeIds, $lat, $lng, 5),
                'nearby_cafes' => ($lat && $lng) ? $this->getNearbyCafes($lat, $lng, $savedCafeIds, 5) : [],
                'trending_cafes' => $this->getTrendingCafes($savedCafeIds, 5),
                'matches_today' => $this->getMatchesToday($userBookedMatchIds),
                'popular_matches' => $this->getPopularMatches($userBookedMatchIds, 5),
                'active_offers' => $this->getActiveOffers(5),
            ];
        });
    }

    private function getFeaturedCafes(array $savedCafeIds, ?float $lat, ?float $lng, int $limit): array
    {
        $cafes = Cafe::with(['branches' => function ($query) {
                $query->where('is_open', true);
            }])
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
                'logo' => $cafe->logo,
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
                'logo' => $cafe->logo,
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
                'logo' => $cafe->logo,
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
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'kick_off' => $match->kick_off,
                'league' => $match->league,
                'status' => $match->status,
                'cafe_name' => $match->branch->cafe->name,
                'branch_name' => $match->branch->name,
                'price_per_seat' => (float) $match->price_per_seat,
                'seats_available' => $match->seats_available,
                'is_booked' => in_array($match->id, $userBookedMatchIds),
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
            ->select('matches.*')
            ->leftJoin('bookings', 'matches.id', '=', 'bookings.match_id')
            ->where('bookings.created_at', '>=', $weekAgo)
            ->whereIn('bookings.status', ['confirmed', 'pending', 'completed'])
            ->where('matches.match_date', '>=', now()->toDateString())
            ->groupBy('matches.id')
            ->selectRaw('COUNT(bookings.id) as booking_count')
            ->orderBy('booking_count', 'desc')
            ->published()
            ->limit($limit)
            ->get();

        return $popularMatches->map(function ($match) use ($userBookedMatchIds) {
            return [
                'id' => $match->id,
                'home_team' => $match->homeTeam->name,
                'away_team' => $match->awayTeam->name,
                'match_date' => $match->match_date->format('Y-m-d'),
                'kick_off' => $match->kick_off,
                'league' => $match->league,
                'status' => $match->status,
                'cafe_name' => $match->branch->cafe->name,
                'price_per_seat' => (float) $match->price_per_seat,
                'booking_count' => $match->booking_count ?? 0,
                'is_booked' => in_array($match->id, $userBookedMatchIds),
            ];
        })->toArray();
    }

    private function getActiveOffers(int $limit): array
    {
        $threeDaysFromNow = now()->addDays(3)->toDateString();

        $offers = Offer::with('cafe')
            ->active()
            ->orderByRaw('is_featured DESC')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $offers->map(function ($offer) use ($threeDaysFromNow) {
            $isExpiringSoon = $offer->valid_until && 
                              $offer->valid_until->lte($threeDaysFromNow);

            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'image' => $offer->image,
                'original_price' => (float) $offer->original_price,
                'offer_price' => (float) $offer->offer_price,
                'discount_percent' => $offer->discount_percent,
                'cafe_name' => $offer->cafe->name,
                'cafe_id' => $offer->cafe_id,
                'valid_until' => $offer->valid_until?->format('Y-m-d'),
                'is_featured' => $offer->is_featured,
                'is_expiring_soon' => $isExpiringSoon,
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
