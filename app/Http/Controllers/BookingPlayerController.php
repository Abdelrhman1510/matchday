<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPlayerRequest;
use App\Http\Resources\BookingPlayerResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookingPlayerController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get booking players
     */
    public function index(int $bookingId, Request $request): JsonResponse
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this booking.',
            ], Response::HTTP_FORBIDDEN);
        }

        $booking->load('players');
        $players = $this->bookingService->getBookingPlayers($booking);

        return response()->json([
            'success' => true,
            'message' => 'Players retrieved successfully',
            'data' => BookingPlayerResource::collection($players),
        ]);
    }

    /**
     * Add player to booking
     */
    public function store(int $bookingId, Request $request): JsonResponse
    {
        // Check booking existence first
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Check ownership before validation
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add players to this booking.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Validate
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'jersey_number' => ['sometimes', 'integer'],
            'position' => ['sometimes', 'string', 'max:100'],
        ]);

        try {
            $player = $this->bookingService->addPlayer($booking, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Player added successfully.',
                'data' => new BookingPlayerResource($player),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add player: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete player from booking
     */
    public function destroy(int $bookingId, int $playerId, Request $request): JsonResponse
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to manage players.',
            ], Response::HTTP_NOT_FOUND);
        }

        $deleted = $this->bookingService->deletePlayer($booking, $playerId);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found in this booking.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Player removed successfully.',
        ]);
    }
}
