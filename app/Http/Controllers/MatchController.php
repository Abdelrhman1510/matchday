<?php

namespace App\Http\Controllers;

use App\Http\Resources\MatchResource;
use App\Http\Resources\MatchDetailResource;
use App\Services\MatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\GameMatch;

class MatchController extends Controller
{
    use ApiResponse;

    protected MatchService $matchService;

    public function __construct(MatchService $matchService)
    {
        $this->matchService = $matchService;
    }

    /**
     * GET /api/v1/matches
     * Get all published matches with filters
     * Filters: ?status, ?date, ?team_id, ?league, ?branch_id
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:upcoming,live,finished,cancelled',
            'date' => 'sometimes|date_format:Y-m-d',
            'team_id' => 'sometimes|integer|exists:teams,id',
            'league' => 'sometimes|string|max:100',
            'branch_id' => 'sometimes|integer|exists:branches,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $filters = $request->only(['status', 'date', 'team_id', 'league', 'branch_id']);
        $perPage = $request->input('per_page', 15);

        $matches = $this->matchService->getMatches($filters, $perPage);

        return $this->successResponse(
            MatchResource::collection($matches),
            'Matches retrieved successfully',
            200,
            [
                'current_page' => $matches->currentPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
                'last_page' => $matches->lastPage(),
            ]
        );
    }

    /**
     * GET /api/v1/matches/live
     * Get live matches with scores
     */
    public function live(): JsonResponse
    {
        $matches = $this->matchService->getLiveMatches();

        return $this->successResponse(
            MatchResource::collection($matches),
            'Live matches retrieved successfully',
            200,
            [
                'total' => $matches->count(),
            ]
        );
    }

    /**
     * GET /api/v1/matches/upcoming
     * Get upcoming matches ordered by date ASC
     */
    public function upcoming(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 15);
        $matches = $this->matchService->getUpcomingMatches($perPage);

        return $this->successResponse(
            MatchResource::collection($matches),
            'Upcoming matches retrieved successfully',
            200,
            [
                'current_page' => $matches->currentPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
                'last_page' => $matches->lastPage(),
            ]
        );
    }

    /**
     * GET /api/v1/matches/popular
     * Get most booked matches this week
     */
    public function popular(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $limit = $request->input('limit', 10);
        $matches = $this->matchService->getPopularMatches($limit);

        return $this->successResponse(
            MatchResource::collection($matches),
            'Popular matches retrieved successfully',
            200,
            [
                'total' => $matches->count(),
            ]
        );
    }

    /**
     * GET /api/v1/matches/{id}
     * Get match full detail with booking stats
     */
    public function show(int $id): JsonResponse
    {
        $match = $this->matchService->getMatchById($id);

        if (!$match) {
            return $this->errorResponse('Match not found', 404);
        }

        return $this->successResponse(
            new MatchDetailResource($match),
            'Match details retrieved successfully'
        );
    }

    /**
     * GET /api/v1/matches/{id}/seating?branch_id={branchId}
     * Get seating map with availability for this match
     * branch_id is required to specify which branch's seating to show
     */
    public function seating(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('branch_id is required', 422, $validator->errors());
        }

        $branchId = (int) $request->query('branch_id');

        $seatingMap = $this->matchService->getSeatingMap($id, $branchId);

        if (!$seatingMap) {
            return $this->errorResponse('Match not found or not available at this branch', 404);
        }

        // Restructure sections for the expected format
        $sections = collect($seatingMap['sections'] ?? [])->map(function ($section) {
            return [
                'section_id' => $section['id'],
                'section_name' => $section['name'],
                'type' => $section['type'] ?? null,
                'total_seats' => $section['total_seats'] ?? 0,
                'extra_cost' => $section['extra_cost'] ?? 0,
                'seats' => collect($section['seats'] ?? [])->map(function ($seat) {
                    return [
                        'id' => $seat['id'],
                        'label' => $seat['label'],
                        'is_available' => $seat['is_available'],
                    ];
                })->toArray(),
            ];
        })->toArray();

        return $this->successResponse(
            $sections,
            'Seating map retrieved successfully'
        );
    }

    /**
     * POST /api/v1/matches/{id}/save
     * Toggle save/unsave a match for the authenticated user
     */
    public function toggleSave(Request $request, int $id): JsonResponse
    {
        $match = GameMatch::find($id);

        if (!$match) {
            return $this->errorResponse('Match not found', 404);
        }

        $user = $request->user();

        if ($user->savedMatches()->where('match_id', $id)->exists()) {
            $user->savedMatches()->detach($id);
            return $this->successResponse(
                ['is_saved' => false],
                'Match unsaved successfully'
            );
        }

        $user->savedMatches()->attach($id);
        return $this->successResponse(
            ['is_saved' => true],
            'Match saved successfully',
            201
        );
    }

    /**
     * GET /api/v1/matches/saved
     * List all saved matches for the authenticated user
     */
    public function saved(Request $request): JsonResponse
    {
        $user = $request->user();

        $matches = $user->savedMatches()
            ->with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->published()
            ->orderByDesc('match_date')
            ->get();

        return $this->successResponse(
            MatchResource::collection($matches),
            'Saved matches retrieved successfully',
            200,
            ['total' => $matches->count()]
        );
    }
}
