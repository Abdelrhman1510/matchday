<?php

namespace App\Http\Controllers;

use App\Http\Resources\StaffResource;
use App\Models\Cafe;
use App\Models\StaffMember;
use App\Services\StaffService;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{
    protected StaffService $staffService;
    protected SubscriptionEnforcementService $enforcement;

    public function __construct(StaffService $staffService, SubscriptionEnforcementService $enforcement)
    {
        $this->staffService = $staffService;
        $this->enforcement = $enforcement;
    }

    /**
     * 1. GET /api/v1/cafe-admin/staff
     * List all staff with role, permissions, invitation status
     * Permission: manage-staff
     */
    public function index(Request $request)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $staff = $this->staffService->listStaff($cafe);

        return response()->json([
            'success' => true,
            'data' => StaffResource::collection(collect($staff)),
            'meta' => [
                'total' => count($staff),
                'pending' => collect($staff)->where('staff_member.invitation_status', 'pending')->count(),
                'accepted' => collect($staff)->where('staff_member.invitation_status', 'accepted')->count(),
            ],
        ]);
    }

    /**
     * 2. POST /api/v1/cafe-admin/staff
     * Invite staff member
     * Permission: manage-staff
     */
    public function store(Request $request)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,manager,staff',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:manage-bookings,view-bookings,manage-matches,view-analytics,manage-offers,manage-menu,manage-branches,manage-seating,scan-qr,check-in-customers,view-occupancy,manage-staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Subscription enforcement: check staff limit
        $check = $this->enforcement->canAddStaff($cafe);
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'limit' => $check['limit'],
                'current' => $check['current'],
            ], 403);
        }

        try {
            $staffMember = $this->staffService->inviteStaff(
                $cafe,
                $request->user(),
                $request->only(['name', 'email', 'role', 'permissions'])
            );

            $staffDetail = $this->staffService->getStaffDetail($staffMember);

            return response()->json([
                'success' => true,
                'message' => 'Staff invitation sent successfully.',
                'data' => new StaffResource($staffDetail),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 3. GET /api/v1/cafe-admin/staff/{id}
     * Get staff detail with permissions
     * Permission: manage-staff
     */
    public function show(Request $request, $id)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $staffMember = StaffMember::where('cafe_id', $cafe->id)
            ->where('id', $id)
            ->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
            ], 404);
        }

        $staffDetail = $this->staffService->getStaffDetail($staffMember);

        return response()->json([
            'success' => true,
            'data' => new StaffResource($staffDetail),
        ]);
    }

    /**
     * 4. PUT /api/v1/cafe-admin/staff/{id}
     * Update staff role and/or permissions
     * Permission: manage-staff
     */
    public function update(Request $request, $id)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:admin,manager,staff',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:manage-bookings,view-bookings,manage-matches,view-analytics,manage-offers,manage-menu,manage-branches,manage-seating,scan-qr,check-in-customers,view-occupancy,manage-staff',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $staffMember = StaffMember::where('cafe_id', $cafe->id)
            ->where('id', $id)
            ->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
            ], 404);
        }

        try {
            $updatedStaff = $this->staffService->updateStaff(
                $staffMember,
                $request->only(['role', 'permissions'])
            );

            $staffDetail = $this->staffService->getStaffDetail($updatedStaff);

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully.',
                'data' => new StaffResource($staffDetail),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 5. DELETE /api/v1/cafe-admin/staff/{id}
     * Remove staff member and revoke permissions
     * Permission: manage-staff
     */
    public function destroy(Request $request, $id)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $staffMember = StaffMember::where('cafe_id', $cafe->id)
            ->where('id', $id)
            ->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
            ], 404);
        }

        try {
            $this->staffService->removeStaff($staffMember);

            return response()->json([
                'success' => true,
                'message' => 'Staff member removed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 6. POST /api/v1/cafe-admin/staff/{id}/resend-invite
     * Resend invitation email
     * Permission: manage-staff
     */
    public function resendInvite(Request $request, $id)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $staffMember = StaffMember::where('cafe_id', $cafe->id)
            ->where('id', $id)
            ->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found.',
            ], 404);
        }

        try {
            $this->staffService->resendInvite($staffMember);

            return response()->json([
                'success' => true,
                'message' => 'Invitation email resent successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 8. GET /api/v1/cafe-admin/roles-permissions
     * Get all available roles and default permissions
     * Permission: manage-staff
     */
    public function rolesPermissions(Request $request)
    {
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $rolesAndPermissions = $this->staffService->getRolesAndPermissions();

        return response()->json([
            'success' => true,
            'data' => $rolesAndPermissions,
        ]);
    }

    /**
     * Invite staff member to a branch
     */
    public function inviteStaff(Request $request, $branchId)
    {
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $branch = $cafe->branches()->find($branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'required|in:admin,manager,staff',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $token = \Illuminate\Support\Str::random(64);
        $invitation = \App\Models\StaffInvitation::create([
            'branch_id' => $branchId,
            'email' => $request->email,
            'role' => $request->role,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff invitation sent',
            'data' => [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'status' => $invitation->status,
            ],
        ], 201);
    }

    /**
     * List staff for a branch
     */
    public function listBranchStaff(Request $request, $branchId)
    {
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $branch = $cafe->branches()->find($branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
        }

        $staff = $branch->staff()->get()->map(function ($user) use ($branchId) {
            $pivot = $user->pivot;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $pivot->role ?? 'staff',
                'permissions' => [],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Staff retrieved',
            'data' => $staff,
        ]);
    }

    /**
     * Update staff permissions
     */
    public function updatePermissions(Request $request, $branchId, $staffId)
    {
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $permissions = $request->input('permissions', []);

        // Clear old permissions
        \Illuminate\Support\Facades\DB::table('branch_staff_permissions')
            ->where('user_id', $staffId)
            ->where('branch_id', $branchId)
            ->delete();

        // Add new permissions
        foreach ($permissions as $perm) {
            \Illuminate\Support\Facades\DB::table('branch_staff_permissions')->insert([
                'user_id' => $staffId,
                'branch_id' => $branchId,
                'permission' => $perm,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated',
        ]);
    }

    /**
     * Remove staff from branch
     */
    public function removeStaff(Request $request, $branchId, $staffId)
    {
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $branch = $cafe->branches()->find($branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
        }

        $branch->staff()->detach($staffId);

        // Also remove permissions
        \Illuminate\Support\Facades\DB::table('branch_staff_permissions')
            ->where('user_id', $staffId)
            ->where('branch_id', $branchId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff removed successfully',
        ]);
    }

    /**
     * Accept staff invitation
     */
    public function acceptInvitation(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invitation = \App\Models\StaffInvitation::where('token', $request->token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired invitation token',
                'errors' => ['token' => ['Invalid or expired invitation token']],
            ], 422);
        }

        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Add user as branch staff
        \Illuminate\Support\Facades\DB::table('branch_staff')->insert([
            'branch_id' => $invitation->branch_id,
            'user_id' => $request->user()->id,
            'role' => $invitation->role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted successfully',
        ]);
    }
}
