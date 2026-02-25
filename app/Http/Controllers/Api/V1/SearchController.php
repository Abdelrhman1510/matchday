<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search across cafes, matches, and cities
     * 
     * @group Search
     * 
     * @queryParam q string required Search term. Example: barcelona
     */
    public function index(Request $request): JsonResponse
    {
        $searchTerm = $request->query('query') ?? $request->query('q');
        $type = $request->query('type');

        if (!$searchTerm) {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required.',
                'errors' => ['query' => ['The query field is required.']],
            ], 422);
        }

        if (strlen($searchTerm) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 3 characters.',
                'errors' => ['query' => ['The query must be at least 3 characters.']],
            ], 422);
        }

        $searchTerm = trim($searchTerm);
        $likeTerm = '%' . $searchTerm . '%';

        $cafes = [];
        $teams = [];
        $matches = [];

        // Search cafes
        if (!$type || $type === 'cafes') {
            $cafes = Cafe::where('name', 'like', $likeTerm)
                ->limit(10)
                ->get()
                ->map(function ($cafe) {
                    return [
                        'id' => $cafe->id,
                        'name' => $cafe->name,
                        'logo' => $cafe->logo,
                        'city' => $cafe->city,
                        'rating' => (float) $cafe->avg_rating,
                    ];
                })->toArray();
        }

        // Search teams
        if (!$type || $type === 'teams') {
            $teams = Team::where('name', 'like', $likeTerm)
                ->limit(10)
                ->get()
                ->map(function ($team) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'logo' => $team->logo,
                        'league' => $team->league,
                    ];
                })->toArray();
        }

        // Search matches
        if (!$type || $type === 'matches') {
            $matches = GameMatch::with(['homeTeam', 'awayTeam'])
                ->where(function ($query) use ($likeTerm) {
                    $query->whereHas('homeTeam', function ($q) use ($likeTerm) {
                        $q->where('name', 'like', $likeTerm);
                    })
                    ->orWhereHas('awayTeam', function ($q) use ($likeTerm) {
                        $q->where('name', 'like', $likeTerm);
                    })
                    ->orWhere('league', 'like', $likeTerm);
                })
                ->where('is_published', true)
                ->limit(10)
                ->get()
                ->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'home_team' => $match->homeTeam->name ?? null,
                        'away_team' => $match->awayTeam->name ?? null,
                        'match_date' => $match->match_date?->format('Y-m-d'),
                        'league' => $match->league,
                    ];
                })->toArray();
        }

        return response()->json([
            'success' => true,
            'message' => 'Search results retrieved.',
            'data' => [
                'cafes' => $cafes,
                'teams' => $teams,
                'matches' => $matches,
            ],
        ]);
    }
}
