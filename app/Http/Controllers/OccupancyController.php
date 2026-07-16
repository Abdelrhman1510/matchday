<?php

namespace App\Http\Controllers;

use App\Services\OccupancyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OccupancyController extends Controller
{
    protected OccupancyService $occupancyService;

    public function __construct(OccupancyService $occupancyService)
    {
        $this->occupancyService = $occupancyService;
    }

    // =========================================
    // HELPER: Get owner's cafe and current branch
    // =========================================

    protected function getOwnerCafe(Request $request)
    {
        return $this->actingCafe($request);
    }

    protected function getCurrentBranch(Request $request)
    {
        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) return null;

        // Phase 2: resolve within the acting user's accessible branches.
        // Prefer the cafe's current branch when it is accessible, else the first accessible branch.
        $accessible = $this->accessibleBranchIds($request);
        $query = $cafe->branches()->whereIn('id', $accessible);

        if ($cafe->current_branch_id && in_array($cafe->current_branch_id, $accessible, true)) {
            return $query->find($cafe->current_branch_id) ?? $query->first();
        }

        return $query->first();
    }

    // =========================================
    // 1. GET OCCUPANCY DASHBOARD
    // GET /api/v1/cafe-admin/occupancy
    // Permission: view-occupancy
    // =========================================

    public function index(Request $request)
    {
        if (!$request->user()->can('view-occupancy')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view occupancy data.',
            ], 403);
        }

        $branch = $this->getCurrentBranch($request);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch found for this cafe owner.',
            ], 404);
        }

        $data = $this->occupancyService->getOccupancyDashboard($branch);

        return response()->json([
            'success' => true,
            'message' => 'Occupancy data retrieved successfully',
            'data' => $data,
        ]);
    }

    // =========================================
    // 2. UPDATE CAPACITY
    // PUT /api/v1/cafe-admin/occupancy/capacity
    // Permission: manage-branches (already exists)
    // =========================================

    public function updateCapacity(Request $request)
    {
        // Authorization is enforced by the route middleware `cafe.permission:manage-seating`
        // (owner or a staff member granted manage-seating). No redundant in-controller check —
        // the previous one required manage-cafe-profile, which staff can never be granted.
        $validator = Validator::make($request->all(), [
            'total_capacity' => 'required|integer|min:1|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch = $this->getCurrentBranch($request);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch found for this cafe owner.',
            ], 404);
        }

        $result = $this->occupancyService->updateCapacity($branch, $request->input('total_capacity'));

        return response()->json([
            'success' => true,
            'message' => 'Branch capacity updated successfully.',
            'data' => $result,
        ]);
    }

    // =========================================
    // 3. GET PEAK TIMES (Historical)
    // GET /api/v1/cafe-admin/occupancy/peak-times
    // Permission: view-occupancy
    // =========================================

    public function peakTimes(Request $request)
    {
        if (!$request->user()->can('view-occupancy')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view occupancy data.',
            ], 403);
        }

        $branch = $this->getCurrentBranch($request);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch found for this cafe owner.',
            ], 404);
        }

        $peakTimes = $this->occupancyService->getHistoricalPeakTimes($branch);

        return response()->json([
            'success' => true,
            'message' => 'Historical peak times retrieved successfully',
            'data' => $peakTimes,
        ]);
    }

    // =========================================
    // 4. GET SECTION BREAKDOWN
    // GET /api/v1/cafe-admin/occupancy/sections
    // Permission: view-occupancy
    // =========================================

    public function sections(Request $request)
    {
        if (!$request->user()->can('view-occupancy')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view occupancy data.',
            ], 403);
        }

        $branch = $this->getCurrentBranch($request);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch found for this cafe owner.',
            ], 404);
        }

        $sections = $this->occupancyService->getSectionBreakdown($branch);

        return response()->json([
            'success' => true,
            'message' => 'Section occupancy breakdown retrieved successfully',
            'data' => $sections,
        ]);
    }
}
