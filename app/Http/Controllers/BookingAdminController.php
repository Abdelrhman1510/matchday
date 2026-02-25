<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingAdminService;
use App\Http\Resources\BookingAdminResource;
use App\Http\Resources\BookingAdminDetailResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingAdminController extends Controller
{
    protected BookingAdminService $bookingService;

    public function __construct(BookingAdminService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    // =========================================
    // HELPER: Get owner's cafe
    // =========================================

    protected function getOwnerCafe(Request $request)
    {
        return $request->user()->ownedCafes()->first();
    }

    protected function getOwnerBooking(Request $request, int $bookingId): ?Booking
    {
        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) return null;

        $branchIds = $cafe->branches()->pluck('id');

        return Booking::where(function($q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds)
              ->orWhereHas('match', function($q2) use ($branchIds) {
                  $q2->whereIn('branch_id', $branchIds);
              });
        })->find($bookingId);
    }

    // =========================================
    // 1. LIST BOOKINGS
    // GET /api/v1/cafe-admin/bookings
    // Permission: view-bookings
    // =========================================

    public function index(Request $request, $branchId = null)
    {
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view bookings.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);

        // If no cafe found (e.g., staff user), try to find cafe through branchId
        if (!$cafe && $branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                $cafe = $branch->cafe;
            }
        }

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $result = $this->bookingService->listBookings($cafe, [
            'status' => $request->query('status'),
            'match_id' => $request->query('match_id'),
            'date' => $request->query('date'),
            'per_page' => $request->query('per_page', 15),
        ]);

        $bookings = $result['bookings'];
        $returningUserIds = $result['returning_user_ids'];

        $data = BookingAdminResource::collectionWithReturning($bookings, $returningUserIds);

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    // =========================================
    // 2. BOOKING DETAIL
    // GET /api/v1/cafe-admin/bookings/{id}
    // Permission: view-bookings
    // =========================================

    public function show(Request $request, int $id)
    {
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view bookings.',
            ], 403);
        }

        $booking = $this->getOwnerBooking($request, $id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or does not belong to your cafe.',
            ], 404);
        }

        $cafe = $this->getOwnerCafe($request);
        $result = $this->bookingService->getBookingDetail($booking, $cafe);

        return response()->json([
            'success' => true,
            'message' => 'Booking detail retrieved successfully',
            'data' => new BookingAdminDetailResource($result),
        ]);
    }

    // =========================================
    // 3. CHECK-IN BOOKING
    // POST /api/v1/cafe-admin/bookings/{id}/check-in
    // Permission: check-in-customers
    // =========================================

    public function checkIn(Request $request, int $id)
    {
        if (!$request->user()->can('check-in-customers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to check in customers.',
            ], 403);
        }

        $booking = $this->getOwnerBooking($request, $id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->bookingService->checkIn($booking);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer checked in successfully.',
            'data' => [
                'id' => $result['booking']->id,
                'booking_code' => $result['booking']->booking_code,
                'status' => $result['booking']->status,
                'checked_in_at' => $result['booking']->checked_in_at->toIso8601String(),
                'customer' => [
                    'name' => $result['booking']->user->name,
                    'phone' => $result['booking']->user->phone,
                ],
                'seats' => $result['booking']->seats->map(fn($seat) => [
                    'label' => $seat->label,
                    'section' => $seat->section?->name,
                ])->toArray(),
            ],
        ]);
    }

    // =========================================
    // 4. CANCEL BOOKING
    // POST /api/v1/cafe-admin/bookings/{id}/cancel
    // Permission: manage-bookings
    // =========================================

    public function cancel(Request $request, int $id)
    {
        if (!$request->user()->can('manage-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage bookings.',
            ], 403);
        }

        $booking = $this->getOwnerBooking($request, $id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or does not belong to your cafe.',
            ], 404);
        }

        $result = $this->bookingService->cancelBooking($booking);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => [
                'seats_released' => $result['seats_released'],
                'refunded' => $result['refunded'],
            ],
        ]);
    }

    // =========================================
    // 5. TODAY'S SUMMARY
    // GET /api/v1/cafe-admin/bookings/today-summary
    // Permission: view-bookings
    // =========================================

    public function todaySummary(Request $request)
    {
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view bookings.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $summary = $this->bookingService->getTodaySummary($cafe);

        return response()->json([
            'success' => true,
            'message' => "Today's booking summary",
            'data' => $summary,
        ]);
    }

    /**
     * Export bookings report
     */
    public function exportReport(Request $request)
    {
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view bookings.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bookings exported',
            'data' => [
                'download_url' => url('/api/v1/exports/bookings.csv'),
            ],
        ]);
    }
}
