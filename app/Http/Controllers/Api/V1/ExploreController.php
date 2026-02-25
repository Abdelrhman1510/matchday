<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $matches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where(function ($q) {
                $q->where('is_published', true)
                    ->orWhere('status', 'published');
            })
            ->limit(20)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'home_team' => $match->homeTeam->name ?? null,
                    'away_team' => $match->awayTeam->name ?? null,
                    'match_date' => $match->match_date?->format('Y-m-d'),
                ];
            });

        $cafes = Cafe::limit(20)->get()->map(function ($cafe) {
            return [
                'id' => $cafe->id,
                'name' => $cafe->name,
                'logo' => $cafe->logo,
            ];
        });

        $popularTeams = Team::where('is_popular', true)
            ->limit(20)
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'logo' => $team->logo,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Explore data retrieved.',
            'data' => [
                'matches' => $matches,
                'cafes' => $cafes,
                'popular_teams' => $popularTeams,
            ],
        ]);
    }
}
