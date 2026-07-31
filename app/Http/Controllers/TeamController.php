<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Http\Resources\TeamCollection;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Get all teams with optional league, country, and search filters
     * GET /api/v1/teams?league=Premier+League&country=England&search=Manchester
     */
    public function index(Request $request): JsonResponse
    {
        $league = $request->query('league');
        $country = $request->query('country');
        $search = $request->query('search');

        $teams = $this->teamService->getAllTeams($league, $country, $search);

        return response()->json([
            'success' => true,
            'message' => 'Teams retrieved successfully',
            'data' => TeamResource::collection($teams),
            'meta' => [
                'total' => $teams->count(),
                'league' => $league,
                'country' => $country,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Get popular teams
     * GET /api/v1/teams/popular
     */
    public function popular(): JsonResponse
    {
        $teams = $this->teamService->getPopularTeams();

        return response()->json([
            'success' => true,
            'message' => 'Popular teams retrieved successfully',
            'data' => TeamResource::collection($teams),
            'meta' => [
                'total' => $teams->count(),
            ],
        ]);
    }

    /**
     * Search teams by name
     * GET /api/v1/teams/search?q=liver
     */
    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');

        if (empty($searchTerm)) {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required',
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ], 422);
        }

        $teams = $this->teamService->searchTeams($searchTerm);

        return response()->json([
            'success' => true,
            'message' => 'Teams search completed',
            'data' => TeamResource::collection($teams),
            'meta' => [
                'total' => $teams->count(),
                'search_term' => $searchTerm,
            ],
        ]);
    }

    /**
     * Get single team by ID
     * GET /api/v1/teams/{id}
     */
    public function show(int $id): JsonResponse
    {
        $team = $this->teamService->getTeamById($id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found',
                'errors' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team retrieved successfully',
            'data' => new TeamResource($team),
            'meta' => (object)[],
        ]);
    }

    /**
     * Get teams filtered by league — BUG-068
     * GET /api/v1/teams/by-league/{league}
     * Example: GET /api/v1/teams/by-league/Spanish+League
     */
    public function byLeague(Request $request, string $league): JsonResponse
    {
        $teams = $this->teamService->getAllTeams($league, null, $request->query('search'));

        return response()->json([
            'success' => true,
            'message' => "Teams for league '{$league}' retrieved successfully",
            'data' => TeamResource::collection($teams),
            'meta' => [
                'total' => $teams->count(),
                'league' => $league,
            ],
        ]);
    }

    /**
     * List all distinct leagues available in the database
     * GET /api/v1/teams/leagues
     */
    public function leagues(): JsonResponse
    {
        $leagues = $this->teamService->getAvailableLeagues();

        return response()->json([
            'success' => true,
            'message' => 'Leagues retrieved successfully',
            'data' => $leagues,
            'meta' => [
                'total' => count($leagues),
            ],
        ]);
    }
}
