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
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Get the default favourite-team picker list (a curated shortlist).
     *
     * With thousands of teams we can't return everything here — the app should
     * use search() for the full catalogue. This returns the popular set
     * (national teams + flagged clubs). Cached 24h; run `php artisan cache:clear`
     * after changing teams. Pass through search()/getAllTeams() for the rest.
     */
    public function getPopularTeams(): Collection
    {
        return Cache::remember('popular_teams', 86400, function () {
            return Team::query()
                ->where('is_popular', true)
                ->orderByRaw("FIELD(type, 'national') DESC")
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Search teams by English name, Arabic name, or short code (typeahead).
     * Limited so the picker's search stays fast across thousands of teams.
     */
    public function searchTeams(string $searchTerm): Collection
    {
        return Team::query()
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('name_ar', 'like', "%{$searchTerm}%")
                    ->orWhere('short_name', 'like', "%{$searchTerm}%");
            })
            ->orderBy('is_popular', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(50)
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
