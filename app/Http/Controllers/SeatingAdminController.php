<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Seat;
use App\Models\SeatingSection;
use App\Services\SeatingAdminService;
use App\Http\Resources\SeatingSectionAdminResource;
use App\Http\Resources\SeatAdminResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeatingAdminController extends Controller
{
    protected SeatingAdminService $seatingService;

    public function __construct(SeatingAdminService $seatingService)
    {
        $this->seatingService = $seatingService;
    }

    // =========================================
    // HELPER: Verify branch belongs to owner
    // =========================================

    protected function getOwnerBranch(Request $request, int $branchId): ?Branch
    {
        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return null;
        }

        return $cafe->branches()->find($branchId);
    }

    protected function getOwnerSection(Request $request, int $sectionId): ?SeatingSection
    {
        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return null;
        }

        $section = SeatingSection::with('branch')->find($sectionId);
        if (!$section) {
            return null;
        }

        // Verify the section's branch belongs to this owner's cafe
        $branch = $cafe->branches()->find($section->branch_id);
        if (!$branch) {
            return null;
        }

        return $section;
    }

    protected function getOwnerSeat(Request $request, int $seatId): ?Seat
    {
        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return null;
        }

        $seat = Seat::with('section.branch')->find($seatId);
        if (!$seat) {
            return null;
        }

        // Verify the seat's section's branch belongs to this owner's cafe
        $branch = $cafe->branches()->find($seat->section->branch_id);
        if (!$branch) {
            return null;
        }

        return $seat;
    }

    // =========================================
    // 1. LIST SECTIONS
    // GET /api/v1/cafe-admin/branches/{id}/sections
    // =========================================

    public function listSections(Request $request, $id)
    {
        $branch = $this->getOwnerBranch($request, $id);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe',
            ], 404);
        }

        $result = $this->seatingService->listSections($branch);

        return response()->json([
            'success' => true,
            'message' => 'Sections retrieved successfully',
            'data' => [
                'sections' => SeatingSectionAdminResource::collection($result['sections']),
                'summary' => $result['summary'],
            ],
        ]);
    }

    // =========================================
    // 2. CREATE SECTION
    // POST /api/v1/cafe-admin/branches/{id}/sections
    // =========================================

    public function createSection(Request $request, $id)
    {
        $branch = $this->getOwnerBranch($request, $id);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string|in:main_screen,vip,premium,standard',
            'total_seats' => 'required|integer|min:1|max:500',
            'extra_cost' => 'sometimes|numeric|min:0',
            'icon' => 'sometimes|nullable|string|max:100',
            'screen_size' => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (!isset($validated['type'])) {
            $validated['type'] = 'standard';
        }

        $section = $this->seatingService->createSection($branch, $validated);

        // Load seats relation so it appears in the response
        $section->load('seats');

        return response()->json([
            'success' => true,
            'message' => "Section '{$section->name}' created with {$section->total_seats} seats",
            'data' => new SeatingSectionAdminResource($section),
        ], 201);
    }

    // =========================================
    // 3. UPDATE SECTION
    // PUT /api/v1/cafe-admin/sections/{id}
    // =========================================

    public function updateSection(Request $request, $id)
    {
        $section = $this->getOwnerSection($request, $id);
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found or does not belong to your cafe',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:main_screen,vip,premium,standard',
            'total_seats' => 'sometimes|integer|min:1|max:500',
            'extra_cost' => 'sometimes|nullable|numeric|min:0',
            'icon' => 'sometimes|nullable|string|max:100',
            'screen_size' => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $section = $this->seatingService->updateSection($section, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => new SeatingSectionAdminResource($section),
        ]);
    }

    // =========================================
    // 4. DELETE SECTION
    // DELETE /api/v1/cafe-admin/sections/{id}
    // =========================================

    public function deleteSection(Request $request, $id)
    {
        $section = $this->getOwnerSection($request, $id);
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found or does not belong to your cafe',
            ], 404);
        }

        $result = $this->seatingService->deleteSection($section);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => ['section' => [$result['message']]],
                'data' => [
                    'active_bookings' => $result['active_bookings'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    // =========================================
    // 5. LIST SEATS IN SECTION
    // GET /api/v1/cafe-admin/sections/{id}/seats
    // =========================================

    public function listSeats(Request $request, $id)
    {
        $section = $this->getOwnerSection($request, $id);
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found or does not belong to your cafe',
            ], 404);
        }

        $result = $this->seatingService->listSeats($section);

        return response()->json([
            'success' => true,
            'message' => 'Seats retrieved successfully',
            'data' => [
                'seats' => SeatAdminResource::collection($result['seats']),
                'section' => $result['section'],
                'summary' => $result['summary'],
            ],
        ]);
    }

    // =========================================
    // 6. BULK ADD SEATS
    // POST /api/v1/cafe-admin/sections/{id}/seats
    // =========================================

    public function bulkAddSeats(Request $request, $id)
    {
        $section = $this->getOwnerSection($request, $id);
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found or does not belong to your cafe',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'count' => 'sometimes|integer|min:1|max:200',
            'seats' => 'sometimes|array|min:1',
            'seats.*.label' => 'required_with:seats|string|max:50',
            'seats.*.price' => 'sometimes|numeric|min:0',
            'prefix' => 'sometimes|string|max:5',
            'start_from' => 'sometimes|integer|min:1',
            'table_number' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // If seats array provided, use it to create seats individually
        if (isset($validated['seats'])) {
            $seats = [];
            foreach ($validated['seats'] as $seatData) {
                $seats[] = $section->seats()->create([
                    'label' => $seatData['label'],
                    'price' => $seatData['price'] ?? 0,
                    'is_available' => true,
                ]);
            }
            $section->update(['total_seats' => $section->seats()->count()]);
            $result = [
                'created_count' => count($seats),
                'seats' => $seats,
                'new_total' => $section->fresh()->total_seats,
            ];
        } else {
            if (!isset($validated['count'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['count' => ['Either count or seats array is required.']],
                ], 422);
            }
            $result = $this->seatingService->bulkAddSeats($section, $validated);
        }

        return response()->json([
            'success' => true,
            'message' => "{$result['created_count']} seat(s) added to section",
            'data' => [
                'created_count' => $result['created_count'],
                'seats' => SeatAdminResource::collection(collect($result['seats'])),
                'new_total' => $result['new_total'],
            ],
        ], 201);
    }

    // =========================================
    // 7. UPDATE SEAT
    // PUT /api/v1/cafe-admin/seats/{id}
    // =========================================

    public function updateSeat(Request $request, $id)
    {
        $seat = $this->getOwnerSeat($request, $id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found or does not belong to your cafe',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'sometimes|string|max:50',
            'price' => 'sometimes|numeric|min:0',
            'table_number' => 'sometimes|nullable|string|max:20',
            'is_available' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seat = $this->seatingService->updateSeat($seat, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seat updated successfully',
            'data' => new SeatAdminResource($seat),
        ]);
    }

    // =========================================
    // 8. DELETE SEAT
    // DELETE /api/v1/cafe-admin/seats/{id}
    // =========================================

    public function deleteSeat(Request $request, $id)
    {
        $seat = $this->getOwnerSeat($request, $id);
        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found or does not belong to your cafe',
            ], 404);
        }

        $result = $this->seatingService->deleteSeat($seat);

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
                'new_section_total' => $result['new_section_total'],
            ],
        ]);
    }

    // =========================================
    // 9. BULK CREATE SECTIONS
    // POST /api/v1/cafe-admin/branches/{id}/sections/bulk
    // =========================================

    public function bulkCreateSections(Request $request, $id)
    {
        $branch = $this->getOwnerBranch($request, $id);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sections' => 'required|array|min:1|max:20',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.type' => 'required|string|in:main_screen,vip,premium,standard',
            'sections.*.total_seats' => 'required|integer|min:1|max:500',
            'sections.*.extra_cost' => 'sometimes|numeric|min:0',
            'sections.*.icon' => 'sometimes|nullable|string|max:100',
            'sections.*.screen_size' => 'sometimes|nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->seatingService->bulkCreateSections($branch, $validator->validated()['sections']);

        return response()->json([
            'success' => true,
            'message' => "{$result['summary']['sections_created']} section(s) created with {$result['summary']['total_seats_created']} total seats",
            'data' => [
                'sections' => SeatingSectionAdminResource::collection(collect($result['sections'])),
                'summary' => $result['summary'],
            ],
        ], 201);
    }

    /**
     * Get seating layout for a branch
     */
    public function seatingLayout(Request $request, $branchId)
    {
        $branch = $this->getOwnerBranch($request, $branchId);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe.',
            ], 404);
        }

        $sections = $branch->seatingSections()->with('seats')->get();

        return response()->json([
            'success' => true,
            'message' => 'Seating layout retrieved',
            'data' => $sections->map(function ($section) {
                return [
                    'section_id' => $section->id,
                    'section_name' => $section->name,
                    'seats' => $section->seats->map(function ($seat) {
                        return [
                            'id' => $seat->id,
                            'label' => $seat->label,
                            'price' => $seat->price ?? 0,
                            'is_available' => $seat->is_available,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Toggle seat availability
     */
    public function toggleAvailability(Request $request, $id)
    {
        $seat = $this->getOwnerSeat($request, $id);

        if (!$seat) {
            return response()->json([
                'success' => false,
                'message' => 'Seat not found or does not belong to your cafe.',
            ], 404);
        }

        $seat->update(['is_available' => !$seat->is_available]);

        return response()->json([
            'success' => true,
            'message' => 'Seat availability toggled',
            'data' => new SeatAdminResource($seat),
        ]);
    }
}
