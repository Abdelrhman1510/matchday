<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\Branch;
use App\Models\BranchAmenity;
use App\Models\BranchHour;
use App\Models\GameMatch;
use App\Services\ImageService;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\CafeAdminResource;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\BranchDetailResource;
use App\Http\Resources\BranchAmenityResource;

class CafeAdminController extends Controller
{
    protected ImageService $imageService;
    protected SubscriptionEnforcementService $enforcement;

    public function __construct(ImageService $imageService, SubscriptionEnforcementService $enforcement)
    {
        $this->imageService = $imageService;
        $this->enforcement = $enforcement;
    }

    // =========================================
    // CAFE MANAGEMENT (Endpoints 1-5)
    // =========================================

    /**
     * 1. Create cafe profile
     * POST /api/v1/cafe-admin/cafe
     */
    public function createCafe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'city' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user already owns a cafe
        if ($request->user()->ownedCafes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already own a cafe',
            ], 400);
        }

        $cafe = Cafe::create([
            'owner_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'phone' => $request->phone ?? '',
            'city' => $request->city ?? '',
            'is_premium' => false,
            'avg_rating' => 0,
            'total_reviews' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cafe created successfully',
            'data' => new CafeAdminResource($cafe),
        ], 201);
    }

    /**
     * 2. Update cafe profile
     * PUT /api/v1/cafe-admin/cafe
     */
    public function updateCafe(Request $request, $cafeId = null)
    {
        if ($cafeId) {
            // Check if the requested cafe belongs to this user
            $cafe = Cafe::find($cafeId);
            if (!$cafe) {
                return response()->json(['success' => false, 'message' => 'Cafe not found'], 404);
            }
            if ($cafe->owner_id !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'You do not own this cafe'], 403);
            }
        } else {
            $cafe = $request->user()->ownedCafes()->first();
            if (!$cafe) {
                return response()->json(['success' => false, 'message' => 'No cafe found for this owner'], 404);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'city' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe->update($request->only(['name', 'description', 'phone', 'city']));

        // Clear cache
        Cache::forget("cafe_{$cafe->id}");

        return response()->json([
            'success' => true,
            'message' => 'Cafe updated successfully',
            'data' => new CafeAdminResource($cafe),
        ]);
    }

    /**
     * 3. Upload cafe logo
     * POST /api/v1/cafe-admin/cafe/logo
     */
    public function uploadLogo(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Delete old logo if exists
            if ($cafe->logo) {
                if (is_array($cafe->logo)) {
                    $this->imageService->delete($cafe->logo);
                } else {
                    Storage::disk('public')->delete($cafe->logo);
                }
            }

            // Store logo file
            $path = $request->file('logo')->store('logos', 'public');

            $cafe->update(['logo' => $path]);

            // Clear cache
            Cache::forget("cafe_{$cafe->id}");

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'data' => [
                    'logo_url' => Storage::disk('public')->url($path),
                    'cafe' => new CafeAdminResource($cafe),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. Get my cafe details
     * GET /api/v1/cafe-admin/cafe
     */
    public function getMyCafe(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->with(['branches'])->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CafeAdminResource($cafe),
        ]);
    }

    /**
     * 5. Get onboarding status
     * GET /api/v1/cafe-admin/onboarding-status
     */
    public function getOnboardingStatus(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => true,
                'data' => [
                    'step' => 1,
                    'cafe_created' => false,
                    'has_branches' => false,
                    'has_staff' => false,
                ],
            ]);
        }

        $hasBranches = $cafe->branches()->exists();
        $hasStaff = $cafe->staffMembers()->exists();

        $step = 1;
        if ($hasBranches && $hasStaff) {
            $step = 'complete';
        } elseif ($hasBranches) {
            $step = 3;
        } else {
            $step = 2;
        }

        $hasSeatingSections = $hasBranches ? $cafe->branches()->first()->seatingSections()->exists() : false;
        $hasPublishedMatch = $hasBranches ? GameMatch::whereIn('branch_id', $cafe->branches()->pluck('id'))->where('is_published', true)->exists() : false;

        $completedSteps = collect([
            'cafe_created' => true,
            'branch_added' => $hasBranches,
            'seating_configured' => $hasSeatingSections,
            'match_published' => $hasPublishedMatch,
        ]);
        $progressPct = (int) round($completedSteps->filter()->count() / $completedSteps->count() * 100);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding status retrieved',
            'data' => [
                'steps' => $completedSteps->toArray(),
                'progress_percentage' => $progressPct,
            ],
        ]);
    }

    // =========================================
    // BRANCH CRUD (Endpoints 6-14)
    // =========================================

    /**
     * 6. List all branches with stats
     * GET /api/v1/cafe-admin/branches
     */
    public function listBranches(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branches = $cafe->branches()
            ->withCount([
                'bookings as active_bookings_count' => function ($query) {
                    $query->where('status', 'confirmed');
                    // Note: Bookings are linked to matches, not direct dates
                },
                'seatingSections as pitches_count'
            ])
            ->with(['reviews'])
            ->get()
            ->map(function ($branch) {
                $branch->rating = $branch->reviews->avg('rating') ?? 0;
                return $branch;
            });

        // Add current branch ID to request for resource
        $request->merge(['currentBranchId' => session('current_branch_id')]);

        return response()->json([
            'success' => true,
            'data' => BranchListResource::collection($branches),
        ]);
    }

    /**
     * 7. Create branch (Step 1: Basic Info)
     * POST /api/v1/cafe-admin/branches
     */
    public function createBranch(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        // Subscription enforcement: check branch limit
        $check = $this->enforcement->canCreateBranch($cafe);
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'limit' => $check['limit'],
                'current' => $check['current'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'sometimes|string|max:20',
            'city' => 'sometimes|string|max:100',
            'area' => 'sometimes|string|max:100',
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
            'total_seats' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch = Branch::create([
            'cafe_id' => $cafe->id,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone ?? null,
            'city' => $request->city ?? null,
            'area' => $request->area ?? null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'total_seats' => $request->total_seats ?? 0,
            'is_open' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully. Proceed to configure hours.',
            'data' => new BranchDetailResource($branch),
        ], 201);
    }

    /**
     * 8. Update branch hours (Step 2: Hours)
     * PUT /api/v1/cafe-admin/branches/{id}/hours
     */
    public function updateBranchHours(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'hours' => 'required|array|min:1',
            'hours.*.day_of_week' => 'required|string',
            'hours.*.opens_at' => 'sometimes|date_format:H:i',
            'hours.*.closes_at' => 'sometimes|date_format:H:i',
            'hours.*.open_time' => 'sometimes|date_format:H:i',
            'hours.*.close_time' => 'sometimes|date_format:H:i',
            'hours.*.is_open' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete existing hours
            $branch->hours()->delete();

            // Create new hours
            foreach ($request->hours as $hourData) {
                // Support both naming conventions: opens_at/closes_at and open_time/close_time
                $openTime = $hourData['opens_at'] ?? $hourData['open_time'] ?? null;
                $closeTime = $hourData['closes_at'] ?? $hourData['close_time'] ?? null;
                $isOpen = $hourData['is_open'] ?? ($openTime !== null);

                BranchHour::create([
                    'branch_id' => $branch->id,
                    'day_of_week' => $hourData['day_of_week'],
                    'is_open' => $isOpen,
                    'open_time' => $isOpen ? $openTime : null,
                    'close_time' => $isOpen ? $closeTime : null,
                ]);
            }

            DB::commit();

            $branch->load('hours');

            return response()->json([
                'success' => true,
                'message' => 'Branch hours updated successfully',
                'data' => new BranchDetailResource($branch),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update hours: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 9. Add amenities in bulk (Step 3: Amenities)
     * POST /api/v1/cafe-admin/branches/{id}/amenities/bulk
     */
    public function addAmenitiesBulk(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        // Support both formats: amenity_ids (pivot) and amenities (create new)
        if ($request->has('amenity_ids')) {
            $validator = Validator::make($request->all(), [
                'amenity_ids' => 'required|array|min:1',
                'amenity_ids.*' => 'required|integer|exists:amenities,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Sync amenities via pivot table
            $branch->amenities()->syncWithoutDetaching($request->amenity_ids);

            return response()->json([
                'success' => true,
                'message' => 'Amenities added successfully',
                'data' => $branch->amenities,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'amenities' => 'required|array|min:1',
            'amenities.*.name' => 'required|string|max:100',
            'amenities.*.icon' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $amenities = [];
            foreach ($request->amenities as $amenityData) {
                $amenities[] = BranchAmenity::create([
                    'branch_id' => $branch->id,
                    'name' => $amenityData['name'],
                    'icon' => $amenityData['icon'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Amenities added successfully',
                'data' => BranchAmenityResource::collection($amenities),
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add amenities: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 10. Get branch detail
     * GET /api/v1/cafe-admin/branches/{id}
     */
    public function getBranch(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()
            ->with(['hours', 'amenities', 'seatingSections', 'cafe'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BranchDetailResource($branch),
        ]);
    }

    /**
     * 11. Update branch basic info
     * PUT /api/v1/cafe-admin/branches/{id}
     */
    public function updateBranch(Request $request, $id)
    {
        // Check if the branch exists
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
            ], 404);
        }

        $cafe = $request->user()->ownedCafes()->first();

        // Check ownership
        if (!$cafe || $branch->cafe_id !== $cafe->id) {
            return response()->json([
                'success' => false,
                'message' => 'This branch does not belong to your cafe',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'phone' => 'sometimes|string|max:20',
            'city' => 'sometimes|string|max:100',
            'area' => 'sometimes|string|max:100',
            'latitude' => 'sometimes|numeric|min:-90|max:90',
            'longitude' => 'sometimes|numeric|min:-180|max:180',
            'total_seats' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $branch->update($request->only(['name', 'address', 'phone', 'city', 'area', 'latitude', 'longitude', 'total_seats']));

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => new BranchDetailResource($branch),
        ]);
    }

    /**
     * 12. Delete branch
     * DELETE /api/v1/cafe-admin/branches/{id}
     */
    public function deleteBranch(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        // Check for active bookings
        $activeBookingsCount = $branch->bookings()
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', now())
            ->count();

        if ($activeBookingsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch with active bookings',
            ], 400);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully',
        ]);
    }

    /**
     * 13. Toggle branch status
     * PUT /api/v1/cafe-admin/branches/{id}/status
     */
    public function toggleBranchStatus(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        $branch->update([
            'is_open' => !$branch->is_open,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch status updated successfully',
            'data' => new BranchDetailResource($branch),
        ]);
    }

    /**
     * 14. Get branch setup progress
     * GET /api/v1/cafe-admin/branches/{id}/setup-progress
     */
    public function getBranchSetupProgress(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        $progress = [
            'basic_info' => true, // Always true if branch exists
            'hours_configured' => $branch->hours()->count() >= 7,
            'hours_set' => $branch->hours()->count() >= 7,
            'amenities_added' => $branch->amenities()->count() > 0,
            'seating_configured' => $branch->seatingSections()->count() > 0,
        ];

        $completedCount = collect($progress)->filter()->count();
        $totalSteps = count($progress);
        $progress['progress_percentage'] = (int) round(($completedCount / $totalSteps) * 100);

        return response()->json([
            'success' => true,
            'message' => 'Setup progress retrieved successfully',
            'data' => $progress,
        ]);
    }

    // =========================================
    // BRANCH OVERVIEW (Endpoint 15)
    // =========================================

    /**
     * 15. Get branch overview (all-in-one)
     * GET /api/v1/cafe-admin/branches/{id}/overview
     */
    public function getBranchOverview(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        // Cache key for this overview
        $cacheKey = "branch_overview_{$id}";

        $data = Cache::remember($cacheKey, 300, function () use ($cafe, $id) {
            $branch = $cafe->branches()
                ->with([
                    'hours' => function ($query) {
                        $query->orderBy('day_of_week');
                    },
                    'amenities',
                    'seatingSections.seats'
                ])
                ->findOrFail($id);

            // Calculate stats - bookings are linked to matches, not direct dates
            // Simplified for now - count total confirmed bookings
            $todayBookings = $branch->bookings()
                ->where('status', 'confirmed')
                ->count();

            $todayMatches = GameMatch::where('branch_id', $branch->id)
                ->whereDate('match_date', now()->toDateString())
                ->count();

            $totalSeats = $branch->seatingSections->sum('total_seats');
            // Simplified occupancy - would need to check bookings through matches
            $occupiedSeats = 0; // Placeholder - bookings are linked to matches
            $occupancyPct = 0; // Placeholder

            $rating = $branch->reviews()->avg('rating') ?? 0;
            $totalReviews = $branch->reviews()->count();

            // Setup progress
            $setupProgress = [
                'basic_info' => true,
                'hours_set' => $branch->hours->count() >= 7,
                'amenities_added' => $branch->amenities->count() > 0,
                'seating_configured' => $branch->seatingSections->count() > 0,
            ];

            // Upcoming matches
            $upcomingMatches = $branch->matches()
                ->where('match_date', '>=', now())
                ->orderBy('match_date')
                ->limit(3)
                ->get();

            return [
                'branch' => new BranchDetailResource($branch),
                'hours' => $branch->hours,
                'amenities' => $branch->amenities,
                'sections' => $branch->seatingSections->map(function ($section) {
                    $occupied = 0;
                    foreach ($section->seats as $seat) {
                        if ($seat->bookings->count() > 0) {
                            $occupied++;
                        }
                    }
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'type' => $section->type,
                        'total_seats' => $section->total_seats,
                        'occupied' => $occupied,
                        'available' => $section->total_seats - $occupied,
                    ];
                }),
                'stats' => [
                    'today_bookings' => $todayBookings,
                    'today_matches' => $todayMatches,
                    'total_matches' => GameMatch::where('branch_id', $branch->id)->count(),
                    'total_bookings' => $branch->bookings()->count(),
                    'total_revenue' => (float) ($branch->bookings()->whereIn('status', ['confirmed', 'checked_in'])->sum('total_amount') ?? 0),
                    'occupancy_pct' => $occupancyPct,
                    'rating' => round($rating, 1),
                    'total_reviews' => $totalReviews,
                ],
                'upcoming_matches' => $upcomingMatches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'home_team' => $match->homeTeam->name ?? null,
                        'away_team' => $match->awayTeam->name ?? null,
                        'match_date' => $match->match_date,
                        'competition' => $match->competition,
                    ];
                }),
                'setup_progress' => $setupProgress,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Branch overview retrieved successfully',
            'data' => $data,
        ]);
    }

    // =========================================
    // SWITCH BRANCH (Endpoints 16-17)
    // =========================================

    /**
     * 17. Switch current branch
     * PUT /api/v1/cafe-admin/current-branch
     */
    public function switchCurrentBranch(Request $request)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify branch belongs to user's cafe
        $branch = $cafe->branches()->find($request->branch_id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found or does not belong to your cafe',
            ], 404);
        }

        // Store in session
        session(['current_branch_id' => $branch->id]);

        return response()->json([
            'success' => true,
            'message' => 'Current branch switched successfully',
            'data' => new BranchDetailResource($branch),
        ]);
    }

    // =========================================
    // AMENITIES (Endpoints 18-20)
    // =========================================

    /**
     * 18. List branch amenities
     * GET /api/v1/cafe-admin/branches/{id}/amenities
     */
    public function listAmenities(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);
        $amenities = $branch->amenities;

        return response()->json([
            'success' => true,
            'data' => BranchAmenityResource::collection($amenities),
        ]);
    }

    /**
     * 19. Add single amenity
     * POST /api/v1/cafe-admin/branches/{id}/amenities
     */
    public function addAmenity(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $branch = $cafe->branches()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'icon' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amenity = BranchAmenity::create([
            'branch_id' => $branch->id,
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Amenity added successfully',
            'data' => new BranchAmenityResource($amenity),
        ], 201);
    }

    /**
     * 20. Remove amenity
     * DELETE /api/v1/cafe-admin/amenities/{id}
     */
    public function removeAmenity(Request $request, $id)
    {
        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        // Find amenity and verify it belongs to user's cafe
        $amenity = BranchAmenity::findOrFail($id);
        $branch = $cafe->branches()->find($amenity->branch_id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Amenity not found or does not belong to your cafe',
            ], 404);
        }

        $amenity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amenity removed successfully',
        ]);
    }

    /**
     * List all cafes owned by the current user
     * GET /api/v1/admin/cafes
     */
    public function listCafes(Request $request)
    {
        $cafes = $request->user()->ownedCafes()
            ->withCount('branches')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Cafes retrieved successfully',
            'data' => $cafes->map(function ($cafe) {
                return [
                    'id' => $cafe->id,
                    'name' => $cafe->name,
                    'logo' => $cafe->logo,
                    'branches_count' => $cafe->branches_count,
                ];
            }),
        ]);
    }
}
