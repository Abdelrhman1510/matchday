<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingDetailResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\ChatRoom;
use App\Models\GameMatch;
use App\Services\BookingService;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected SubscriptionEnforcementService $enforcement;

    public function __construct(BookingService $bookingService, SubscriptionEnforcementService $enforcement)
    {
        $this->bookingService = $bookingService;
        $this->enforcement = $enforcement;
    }

    /**
     * Create a new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        // Subscription enforcement: check booking limit for the cafe
        $match = GameMatch::with('branch.cafe')->find($request->match_id);
        if ($match && $match->branch && $match->branch->cafe) {
            $check = $this->enforcement->canReceiveBooking($match->branch->cafe);
            if (!$check['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $check['reason'],
                    'limit' => $check['limit'],
                    'current' => $check['current'],
                ], 403);
            }
        }

        try {
            $booking = $this->bookingService->createBooking(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => new BookingDetailResource($booking),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's bookings
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tab' => ['nullable', 'string', 'in:upcoming,past,cancelled'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,checked_in,cancelled,past'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = $request->only(['tab', 'status']);
        $perPage = $request->input('per_page', 15);

        $bookings = $this->bookingService->getUserBookings(
            $request->user(),
            $filters,
            $perPage
        );

        // Get tab counts
        $tabCounts = $this->bookingService->getTabCounts($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'tabs' => $tabCounts,
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Get booking detail
     */
    public function show(int $id, Request $request): JsonResponse
    {
        // First check if booking exists at all
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Check ownership
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this booking.',
            ], Response::HTTP_FORBIDDEN);
        }

        $booking = $this->bookingService->getBookingById($id, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Booking details retrieved successfully',
            'data' => new BookingDetailResource($booking),
        ]);
    }

    /**
     * Update booking
     */
    public function update(int $id, UpdateBookingRequest $request): JsonResponse
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to update it.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $booking = $this->bookingService->updateBooking($booking, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully.',
                'data' => new BookingDetailResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cancel booking
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to cancel it.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already cancelled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $booking = $this->bookingService->cancelBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully.',
                'data' => new BookingDetailResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get entry pass
     */
    public function pass(int $id, Request $request): JsonResponse
    {
        $booking = Booking::with(['match.homeTeam', 'match.awayTeam', 'branch.cafe', 'seats.section'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to view it.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return response()->json([
                'success' => false,
                'message' => 'Entry pass is only available for confirmed bookings.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $passData = $this->bookingService->getEntryPass($booking);

        return response()->json([
            'success' => true,
            'data' => $passData,
        ]);
    }

    /**
     * Get shareable booking data
     */
    public function share(int $id, Request $request): JsonResponse
    {
        $booking = Booking::with(['match.homeTeam', 'match.awayTeam', 'branch.cafe', 'seats'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to share it.',
            ], Response::HTTP_NOT_FOUND);
        }

        $shareData = $this->bookingService->getShareableData($booking);

        return response()->json([
            'success' => true,
            'data' => $shareData,
        ]);
    }

    /**
     * Get calendar ICS data
     */
    public function addToCalendar(int $id, Request $request): Response|JsonResponse
    {
        $booking = Booking::with(['match.homeTeam', 'match.awayTeam', 'branch.cafe', 'seats'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or you do not have permission to access it.',
            ], Response::HTTP_NOT_FOUND);
        }

        $icsData = $this->bookingService->getCalendarData($booking);

        return response($icsData, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="match-booking-' . $booking->booking_code . '.ics"');
    }

    /**
     * POST /api/v1/bookings/{id}/rebook
     * Find next upcoming match with same teams for quick rebooking.
     */
    public function rebook(int $id, Request $request): JsonResponse
    {
        $booking = Booking::with(['match.homeTeam', 'match.awayTeam', 'branch.cafe'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$booking->match) {
            return response()->json([
                'success' => false,
                'message' => 'Original match data not available.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $homeTeamId = $booking->match->home_team_id;
        $awayTeamId = $booking->match->away_team_id;

        // Find next upcoming match with same teams (either home/away combination)
        $suggestedMatch = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->where('status', 'upcoming')
            ->where('match_date', '>=', now()->toDateString())
            ->where('is_published', true)
            ->where(function ($q) use ($homeTeamId, $awayTeamId) {
                $q->where(function ($inner) use ($homeTeamId, $awayTeamId) {
                    $inner->where('home_team_id', $homeTeamId)
                        ->where('away_team_id', $awayTeamId);
                })->orWhere(function ($inner) use ($homeTeamId, $awayTeamId) {
                    $inner->where('home_team_id', $awayTeamId)
                        ->where('away_team_id', $homeTeamId);
                });
            })
            ->orderBy('match_date')
            ->orderBy('kick_off')
            ->first();

        if ($suggestedMatch) {
            return response()->json([
                'success' => true,
                'message' => 'Suggested match found for rebooking.',
                'data' => [
                    'suggested_match' => [
                        'id' => $suggestedMatch->id,
                        'home_team' => $suggestedMatch->homeTeam ? [
                            'id' => $suggestedMatch->homeTeam->id,
                            'name' => $suggestedMatch->homeTeam->name,
                            'short_name' => $suggestedMatch->homeTeam->short_name,
                            'logo' => $suggestedMatch->homeTeam->logo,
                        ] : null,
                        'away_team' => $suggestedMatch->awayTeam ? [
                            'id' => $suggestedMatch->awayTeam->id,
                            'name' => $suggestedMatch->awayTeam->name,
                            'short_name' => $suggestedMatch->awayTeam->short_name,
                            'logo' => $suggestedMatch->awayTeam->logo,
                        ] : null,
                        'match_date' => $suggestedMatch->match_date,
                        'kick_off' => $suggestedMatch->kick_off,
                        'league' => $suggestedMatch->league,
                        'seats_available' => $suggestedMatch->seats_available,
                        'price_per_seat' => $suggestedMatch->price_per_seat,
                    ],
                    'branch' => $suggestedMatch->branch ? [
                        'id' => $suggestedMatch->branch->id,
                        'name' => $suggestedMatch->branch->name,
                        'address' => $suggestedMatch->branch->address,
                        'cafe' => $suggestedMatch->branch->cafe ? [
                            'id' => $suggestedMatch->branch->cafe->id,
                            'name' => $suggestedMatch->branch->cafe->name,
                        ] : null,
                    ] : null,
                ],
            ]);
        }

        // No exact match found, return similar upcoming matches (any match with either team)
        $similarMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->where('status', 'upcoming')
            ->where('match_date', '>=', now()->toDateString())
            ->where('is_published', true)
            ->where(function ($q) use ($homeTeamId, $awayTeamId) {
                $q->where('home_team_id', $homeTeamId)
                    ->orWhere('away_team_id', $homeTeamId)
                    ->orWhere('home_team_id', $awayTeamId)
                    ->orWhere('away_team_id', $awayTeamId);
            })
            ->orderBy('match_date')
            ->orderBy('kick_off')
            ->limit(5)
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'home_team' => $match->homeTeam ? [
                        'id' => $match->homeTeam->id,
                        'name' => $match->homeTeam->name,
                        'short_name' => $match->homeTeam->short_name,
                        'logo' => $match->homeTeam->logo,
                    ] : null,
                    'away_team' => $match->awayTeam ? [
                        'id' => $match->awayTeam->id,
                        'name' => $match->awayTeam->name,
                        'short_name' => $match->awayTeam->short_name,
                        'logo' => $match->awayTeam->logo,
                    ] : null,
                    'match_date' => $match->match_date,
                    'kick_off' => $match->kick_off,
                    'league' => $match->league,
                    'seats_available' => $match->seats_available,
                    'price_per_seat' => $match->price_per_seat,
                    'branch' => $match->branch ? [
                        'id' => $match->branch->id,
                        'name' => $match->branch->name,
                        'cafe' => $match->branch->cafe ? [
                            'id' => $match->branch->cafe->id,
                            'name' => $match->branch->cafe->name,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'No exact rematch found. Here are similar upcoming matches.',
            'data' => [
                'suggested_match' => null,
                'similar_matches' => $similarMatches,
            ],
        ]);
    }

    /**
     * POST /api/v1/bookings/{id}/enter-fan-room
     * Enter/create fan room chat for a live match booking.
     */
    public function enterFanRoom(int $id, Request $request): JsonResponse
    {
        $booking = Booking::with(['match', 'branch'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Only confirmed or checked_in bookings
        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return response()->json([
                'success' => false,
                'message' => 'Fan room is only available for confirmed or checked-in bookings.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Match must be live
        if (!$booking->match || !$booking->match->is_live) {
            return response()->json([
                'success' => false,
                'message' => 'Fan room is only available when the match is live.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Create or get existing chat room (match_id + branch_id)
        $chatRoom = ChatRoom::firstOrCreate(
            [
                'match_id' => $booking->match_id,
                'branch_id' => $booking->branch_id,
            ],
            [
                'cafe_id' => $booking->branch->cafe_id ?? null,
                'name' => 'Fan Room - Match #' . $booking->match_id,
                'type' => 'cafe',
                'is_active' => true,
                'viewers_count' => 0,
            ]
        );

        // Add user to room members if not already
        if (!$chatRoom->members()->where('user_id', $request->user()->id)->exists()) {
            $chatRoom->members()->attach($request->user()->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fan room entered successfully.',
            'data' => [
                'chat_room_id' => $chatRoom->id,
            ],
        ]);
    }
}
