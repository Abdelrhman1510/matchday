<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Services\MatchAdminService;
use App\Services\SubscriptionEnforcementService;
use App\Http\Resources\MatchAdminResource;
use App\Http\Resources\MatchAdminDetailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MatchAdminController extends Controller
{
    protected MatchAdminService $matchService;
    protected SubscriptionEnforcementService $enforcement;

    public function __construct(MatchAdminService $matchService, SubscriptionEnforcementService $enforcement)
    {
        $this->matchService = $matchService;
        $this->enforcement = $enforcement;
    }

    // =========================================
    // HELPER: Get owner's cafe
    // =========================================

    protected function getOwnerCafe(Request $request)
    {
        return $request->user()->ownedCafes()->first();
    }

    protected function getOwnerMatch(Request $request, int $matchId): ?GameMatch
    {
        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return null;
        }

        $branchIds = $cafe->branches()->pluck('id');

        return GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->whereIn('branch_id', $branchIds)
            ->find($matchId);
    }

    // =========================================
    // 1. LIST MATCHES
    // GET /api/v1/cafe-admin/matches
    // =========================================

    public function index(Request $request)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $result = $this->matchService->listMatches($cafe, [
            'status' => $request->query('status'),
            'branch_id' => $request->query('branch_id'),
            'per_page' => $request->query('per_page', 15),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Matches retrieved successfully',
            'data' => MatchAdminResource::collection($result['matches']),
            'meta' => [
                'current_page' => $result['matches']->currentPage(),
                'last_page' => $result['matches']->lastPage(),
                'per_page' => $result['matches']->perPage(),
                'total' => $result['matches']->total(),
            ],
        ]);
    }

    // =========================================
    // 2. CREATE MATCH
    // POST /api/v1/cafe-admin/matches
    // =========================================

    public function store(Request $request, $branchId = null)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Subscription enforcement: check match limit
        $check = $this->enforcement->canCreateMatch($cafe);
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'limit' => $check['limit'],
                'current' => $check['current'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|integer',
            'home_team_id' => 'required|integer|exists:teams,id',
            'away_team_id' => 'required|integer|exists:teams,id|different:home_team_id',
            'league' => 'sometimes|string|max:100',
            'match_date' => 'required|date|after_or_equal:today',
            'kick_off' => 'sometimes|date_format:H:i',
            'seats_available' => 'sometimes|integer|min:1',
            'price_per_seat' => 'sometimes|numeric|min:0',
            'ticket_price' => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:1|max:300',
            'booking_opens_at' => 'sometimes|nullable|date',
            'booking_closes_at' => 'sometimes|nullable|date',
            'field_name' => 'sometimes|nullable|string|max:100',
            'venue_name' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Use route branchId or request body branch_id
        $resolvedBranchId = $branchId ?? $request->branch_id;

        // Verify branch belongs to this cafe
        $branch = $cafe->branches()->find($resolvedBranchId);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe.',
            ], 404);
        }

        $match = $this->matchService->createMatch($branch->id, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match created successfully (unpublished)',
            'data' => new MatchAdminResource($match),
        ], 201);
    }

    // =========================================
    // 3. MATCH DETAIL
    // GET /api/v1/cafe-admin/matches/{id}
    // =========================================

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->matchService->getMatchDetail($match);

        return response()->json([
            'success' => true,
            'message' => 'Match detail retrieved successfully',
            'data' => new MatchAdminDetailResource($result),
        ]);
    }

    // =========================================
    // 4. UPDATE MATCH
    // PUT /api/v1/cafe-admin/matches/{id}
    // =========================================

    public function update(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'home_team_id' => 'sometimes|integer|exists:teams,id',
            'away_team_id' => 'sometimes|integer|exists:teams,id',
            'league' => 'sometimes|string|max:100',
            'match_date' => 'sometimes|date|after_or_equal:today',
            'kick_off' => 'sometimes|date_format:H:i',
            'seats_available' => 'sometimes|integer|min:1',
            'price_per_seat' => 'sometimes|numeric|min:0',
            'ticket_price' => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:1|max:300',
            'booking_opens_at' => 'sometimes|nullable|date',
            'booking_closes_at' => 'sometimes|nullable|date',
            'field_name' => 'sometimes|nullable|string|max:100',
            'venue_name' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate home != away if both provided
        $validated = $validator->validated();
        $homeId = $validated['home_team_id'] ?? $match->home_team_id;
        $awayId = $validated['away_team_id'] ?? $match->away_team_id;
        if ($homeId === $awayId) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['away_team_id' => ['Home team and away team must be different.']],
            ], 422);
        }

        $result = $this->matchService->updateMatch($match, $validated);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Match updated successfully',
            'data' => new MatchAdminResource($result['match']),
        ]);
    }

    // =========================================
    // 5. DELETE (CANCEL) MATCH
    // DELETE /api/v1/cafe-admin/matches/{id}
    // =========================================

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->matchService->cancelMatch($match);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'bookings_cancelled' => $result['bookings_cancelled'],
                'bookings_refunded' => $result['bookings_refunded'],
                'users_notified' => $result['users_notified'],
            ],
        ]);
    }

    // =========================================
    // 6. PUBLISH MATCH
    // POST /api/v1/cafe-admin/matches/{id}/publish
    // =========================================

    public function publish(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->matchService->publishMatch($match);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new MatchAdminResource($result['match']),
        ]);
    }

    // =========================================
    // 7. UPDATE SCORE
    // PUT /api/v1/cafe-admin/matches/{id}/score
    // =========================================

    public function updateScore(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'home_score' => 'required|integer|min:0|max:99',
            'away_score' => 'required|integer|min:0|max:99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->matchService->updateScore($match, $request->home_score, $request->away_score);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new MatchAdminResource($result['match']),
        ]);
    }

    // =========================================
    // 8. UPDATE STATUS
    // PUT /api/v1/cafe-admin/matches/{id}/status
    // =========================================

    public function updateStatus(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:upcoming,live,finished',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->matchService->updateStatus($match, $request->status);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new MatchAdminResource($result['match']),
        ]);
    }

    // =========================================
    // 9. SEND REMINDER
    // POST /api/v1/cafe-admin/matches/{id}/reminder
    // =========================================

    public function sendReminder(Request $request, $id)
    {
        if (!$request->user()->can('manage-matches')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage matches.',
            ], 403);
        }

        $match = $this->getOwnerMatch($request, $id);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->matchService->sendReminder($match);

        if (!$result['success']) {
            $statusCode = isset($result['last_sent_at']) ? 429 : 404;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => isset($result['last_sent_at']) ? ['last_sent_at' => $result['last_sent_at']] : null,
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'users_notified' => $result['users_notified'],
            ],
        ]);
    }

    /**
     * Start a match (set is_live = true)
     */
    public function startMatch(Request $request, $id)
    {
        $match = $this->getOwnerMatch($request, $id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $match->update([
            'is_live' => true,
            'status' => 'live',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Match started successfully',
            'data' => new MatchAdminResource($match->fresh(['homeTeam', 'awayTeam'])),
        ]);
    }

    /**
     * End a match (set status = completed, is_live = false)
     */
    public function endMatch(Request $request, $id)
    {
        $match = $this->getOwnerMatch($request, $id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $match->update([
            'status' => 'completed',
            'is_live' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Match ended successfully',
            'data' => new MatchAdminResource($match->fresh(['homeTeam', 'awayTeam'])),
        ]);
    }

    /**
     * Cancel a match and process refunds
     */
    public function cancelMatch(Request $request, $id)
    {
        $match = $this->getOwnerMatch($request, $id);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found or does not belong to your cafe.',
            ], 404);
        }

        $match->update([
            'status' => 'cancelled',
            'is_live' => false,
        ]);

        // Process refunds for all confirmed bookings
        $bookings = \App\Models\Booking::where('match_id', $match->id)
            ->where('status', 'confirmed')
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled']);
            // Refund payments
            \App\Models\Payment::where('booking_id', $booking->id)
                ->where(function ($query) {
                    $query->where('status', 'paid')
                        ->orWhere('status', 'completed');
                })
                ->update(['status' => 'refunded']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Match cancelled and refunds processed',
            'data' => new MatchAdminResource($match->fresh(['homeTeam', 'awayTeam'])),
        ]);
    }
}
