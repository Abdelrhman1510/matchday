<?php

namespace App\Services;

use App\Models\Cafe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CafeService
{
    /**
     * Get cafes with filters and pagination
     */
    public function getCafes(?bool $featured = null, ?string $city = null, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Cafe::query()
            ->withCount('branches')
            ->with(['branches' => function ($query) {
                $query->select('id', 'cafe_id', 'name', 'is_open', 'total_seats');
            }]);

        // Filter by featured
        if ($featured !== null && $featured) {
            $query->where('is_featured', true);
        }

        // Filter by city
        if ($city) {
            $query->byCity($city);
        }

        // Search by name or description
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('avg_rating', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
    }

    /**
     * Search cafes by name and city
     */
    public function searchCafes(string $query, ?string $city = null): Collection
    {
        $cafesQuery = Cafe::query()
            ->withCount('branches')
            ->with(['branches' => function ($query) {
                $query->select('id', 'cafe_id', 'name', 'is_open', 'total_seats');
            }])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });

        if ($city) {
            $cafesQuery->byCity($city);
        }

        return $cafesQuery->orderBy('avg_rating', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get nearby cafes using Haversine formula
     */
    public function getNearbyCafes(float $lat, float $lng, float $radius = 10): Collection
    {
        // Get cafes with their branches that are within the radius
        $nearbyBranches = \App\Models\Branch::query()
            ->selectRaw('branches.*, 
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) 
                * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) 
                * sin( radians( latitude ) ) ) ) AS distance', [$lat, $lng, $lat])
            ->whereRaw('( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) 
                * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) 
                * sin( radians( latitude ) ) ) ) < ?', [$lat, $lng, $lat, $radius])
            ->orderBy('distance')
            ->get();

        // Group branches by cafe and get unique cafes
        $cafeIds = $nearbyBranches->pluck('cafe_id')->unique();

        return Cafe::query()
            ->whereIn('id', $cafeIds)
            ->withCount('branches')
            ->with(['branches' => function ($query) use ($lat, $lng) {
                $query->selectRaw('branches.*, 
                    ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) 
                    * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) 
                    * sin( radians( latitude ) ) ) ) AS distance', [$lat, $lng, $lat])
                    ->orderBy('distance');
            }])
            ->get()
            ->sortBy(function ($cafe) {
                return $cafe->branches->first()->distance ?? PHP_INT_MAX;
            })
            ->values();
    }

    /**
     * Get cafe by ID with branches
     */
    public function getCafeById(int $id): ?Cafe
    {
        return Cafe::query()
            ->withCount('branches')
            ->with(['branches' => function ($query) {
                $query->with(['hours', 'amenities'])
                    ->orderBy('name');
            }])
            ->find($id);
    }

    /**
     * Get cafe branches
     */
    public function getCafeBranches(int $cafeId): ?Collection
    {
        $cafe = Cafe::find($cafeId);
        
        if (!$cafe) {
            return null;
        }

        return $cafe->branches()
            ->with(['hours', 'amenities'])
            ->orderBy('name')
            ->get();
    }
}
