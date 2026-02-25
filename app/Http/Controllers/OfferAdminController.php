<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Models\Branch;
use App\Services\OfferAdminService;
use App\Services\SubscriptionEnforcementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OfferAdminController extends Controller
{
    protected OfferAdminService $offerService;
    protected SubscriptionEnforcementService $enforcement;

    public function __construct(OfferAdminService $offerService, SubscriptionEnforcementService $enforcement)
    {
        $this->offerService = $offerService;
        $this->enforcement = $enforcement;
    }

    /**
     * 1. GET /api/v1/cafe-admin/offers
     * Get all offers with optional status filter
     * Permission: manage-offers
     */
    public function index(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Validate status filter
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,expired,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status filter.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $offers = $this->offerService->list($cafe, $request->query('status'));

        return response()->json([
            'success' => true,
            'data' => OfferResource::collection($offers),
        ]);
    }

    /**
     * 2. POST /api/v1/cafe-admin/offers
     * Create a new offer
     * Permission: manage-offers
     */
    public function store(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Subscription enforcement: check offer limit
        $check = $this->enforcement->canCreateOffer($cafe);
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'limit' => $check['limit'],
                'current' => $check['current'],
            ], 403);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:percentage,bogo,free_item',
            'discount_percent' => 'nullable|integer|min:1|max:100',
            'original_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'available_for' => 'required|in:all,weekend,prime_time',
            'terms' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $offer = $this->offerService->create($cafe, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Offer created successfully.',
                'data' => new OfferResource($offer),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 3. GET /api/v1/cafe-admin/offers/{id}
     * Get offer details
     * Permission: manage-offers
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        try {
            $offer = $this->offerService->getDetail($cafe, $id);

            return response()->json([
                'success' => true,
                'data' => new OfferResource($offer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }
    }

    /**
     * 4. PUT /api/v1/cafe-admin/offers/{id}
     * Update offer
     * Permission: manage-offers
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Find offer
        $offer = $cafe->offers()->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:percentage,bogo,free_item',
            'discount_percent' => 'nullable|integer|min:1|max:100',
            'original_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'valid_until' => 'nullable|date|after:today',
            'available_for' => 'sometimes|in:all,weekend,prime_time',
            'terms' => 'nullable|string',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedOffer = $this->offerService->update($offer, $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Offer updated successfully.',
                'data' => new OfferResource($updatedOffer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 5. DELETE /api/v1/cafe-admin/offers/{id}
     * Delete offer (soft delete)
     * Permission: manage-offers
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Find offer
        $offer = $cafe->offers()->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        try {
            $this->offerService->delete($offer);

            return response()->json([
                'success' => true,
                'message' => 'Offer deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 6. POST /api/v1/cafe-admin/offers/{id}/upload-image
     * Upload offer image
     * Permission: manage-offers
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Find offer
        $offer = $cafe->offers()->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedOffer = $this->offerService->uploadImage($offer, $request->file('image'));

            return response()->json([
                'success' => true,
                'message' => 'Offer image uploaded successfully.',
                'data' => new OfferResource($updatedOffer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 7. PUT /api/v1/cafe-admin/offers/{id}/status
     * Update offer status (active/draft toggle)
     * Permission: manage-offers
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('manage-offers')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage offers.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Find offer
        $offer = $cafe->offers()->find($id);

        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found.',
            ], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedOffer = $this->offerService->updateStatus($offer, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Offer status updated successfully.',
                'data' => new OfferResource($updatedOffer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // =========================================================================
    // Branch-based methods (used by tests)
    // =========================================================================

    /**
     * POST /api/v1/admin/branches/{branchId}/offers
     */
    public function storeForBranch(Request $request, $branchId): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $branch = $cafe->branches()->find($branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|string|in:percentage,bogo,free_item,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->discount_type === 'percentage' && $value > 100) {
                        $fail('The discount value cannot exceed 100 for percentage type.');
                    }
                },
            ],
            'valid_from' => 'nullable|date',
            'valid_until' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->valid_from && $value && $value < $request->valid_from) {
                        $fail('The valid until must be a date after valid from.');
                    }
                },
            ],
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $offer = Offer::create([
            'branch_id' => $branchId,
            'cafe_id' => $cafe->id,
            'title' => $request->title,
            'description' => $request->description ?? '',
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'discount' => $request->discount_value,
            'type' => $request->discount_type,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'is_active' => $request->input('is_active', true),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully.',
            'data' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'discount_type' => $offer->discount_type,
                'discount_value' => $offer->discount_value,
            ],
        ], 201);
    }

    /**
     * GET /api/v1/admin/branches/{branchId}/offers
     */
    public function listForBranch(Request $request, $branchId): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $branch = $cafe->branches()->find($branchId);
        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found.'], 404);
        }

        $offers = Offer::where('branch_id', $branchId)->get()->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'discount_type' => $offer->discount_type,
                'is_active' => $offer->is_active,
                'valid_until' => $offer->valid_until?->toDateString(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Offers retrieved successfully.',
            'data' => $offers,
        ]);
    }

    /**
     * POST /api/v1/admin/offers/{id}/image
     */
    public function uploadImageBranch(Request $request, $id): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'Offer not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('offers', 'public');
        $offer->update(['image' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Offer image uploaded successfully.',
            'data' => [
                'image_url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * PUT /api/v1/admin/offers/{id}
     */
    public function updateBranch(Request $request, $id): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'Offer not found.'], 404);
        }

        $offer->update($request->only(['title', 'description', 'discount_type', 'discount_value', 'valid_from', 'valid_until', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully.',
            'data' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'discount_type' => $offer->discount_type,
                'discount_value' => $offer->discount_value,
            ],
        ]);
    }

    /**
     * PUT /api/v1/admin/offers/{id}/toggle-status
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'Offer not found.'], 404);
        }

        $offer->update(['is_active' => !$offer->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Offer status toggled.',
        ]);
    }

    /**
     * DELETE /api/v1/admin/offers/{id} (soft delete)
     */
    public function deleteBranch(Request $request, $id): JsonResponse
    {
        if (!$request->user()->can('manage-offers')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to manage offers.'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['success' => false, 'message' => 'Offer not found.'], 404);
        }

        $offer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offer deleted successfully.',
        ]);
    }
}
