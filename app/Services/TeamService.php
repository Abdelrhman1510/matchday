<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TeamService
{
    /**
     * Get all teams with optional league, country, and search filters
     */
    public function getAllTeams(?string $league = null, ?string $country = null, ?string $search = null): Collection
    {
        $query = Team::query();

        if ($league) {
            $query->byLeague($league);
        }

        if ($country) {
            $query->where('country', $country);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Get popular teams
     * Cached for 24 hours
     */
    public function getPopularTeams(): Collection
    {
        return Cache::remember('popular_teams', 86400, function () {
            return Team::popular()->get();
        });
    }

    /**
     * Search teams by name or short name
     */
    public function searchTeams(string $searchTerm): Collection
    {
        return Team::query()
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('short_name', 'like', "%{$searchTerm}%");
            })
            ->orderBy('is_popular', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get single team by ID
     */
    public function getTeamById(int $id): ?Team
    {
        return Team::find($id);
    }
}
