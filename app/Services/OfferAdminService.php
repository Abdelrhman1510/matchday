<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OfferAdminService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Get all offers for a cafe with optional status filter
     */
    public function list(Cafe $cafe, ?string $status = null)
    {
        $query = $cafe->offers()
            ->orderBy('created_at', 'desc');

        if ($status) {
            if ($status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                      ->orWhere('valid_until', '<', now()->toDateString());
                });
            } else {
                $query->where('status', $status);
            }
        }

        return $query->get();
    }

    /**
     * Create a new offer
     */
    public function create(Cafe $cafe, array $data): Offer
    {
        return DB::transaction(function () use ($cafe, $data) {
            // Default status is 'draft'
            $data['cafe_id'] = $cafe->id;
            $data['status'] = $data['status'] ?? 'draft';
            $data['usage_count'] = 0;

            $offer = Offer::create($data);

            // Clear cache
            $this->clearOfferCache($cafe->id);

            return $offer->fresh();
        });
    }

    /**
     * Get offer details by ID
     */
    public function getDetail(Cafe $cafe, int $offerId): ?Offer
    {
        return $cafe->offers()->findOrFail($offerId);
    }

    /**
     * Update offer
     */
    public function update(Offer $offer, array $data): Offer
    {
        return DB::transaction(function () use ($offer, $data) {
            $offer->update($data);

            // Clear cache
            $this->clearOfferCache($offer->cafe_id);

            return $offer->fresh();
        });
    }

    /**
     * Delete offer (soft delete)
     */
    public function delete(Offer $offer): bool
    {
        $cafeId = $offer->cafe_id;
        
        // Delete image if exists
        if ($offer->image && is_array($offer->image)) {
            $this->imageService->delete($offer->image);
        }

        $offer->delete();

        // Clear cache
        $this->clearOfferCache($cafeId);

        return true;
    }

    /**
     * Upload offer image
     */
    public function uploadImage(Offer $offer, $file): Offer
    {
        return DB::transaction(function () use ($offer, $file) {
            // Delete old image if exists
            if ($offer->image && is_array($offer->image)) {
                $this->imageService->delete($offer->image);
            }

            // Upload new image (multi-size)
            $imagePaths = $this->imageService->upload($file, 'offers');

            $offer->update(['image' => $imagePaths]);

            // Clear cache
            $this->clearOfferCache($offer->cafe_id);

            return $offer->fresh();
        });
    }

    /**
     * Update offer status (active/draft toggle)
     */
    public function updateStatus(Offer $offer, string $status): Offer
    {
        if (!in_array($status, ['active', 'draft'])) {
            throw new \InvalidArgumentException('Status must be either "active" or "draft"');
        }

        $offer->update(['status' => $status]);

        // Clear cache
        $this->clearOfferCache($offer->cafe_id);

        return $offer->fresh();
    }

    /**
     * Clear offers cache for a cafe
     */
    private function clearOfferCache(int $cafeId): void
    {
        Cache::forget("cafe_offers_{$cafeId}");
        Cache::forget("cafe_active_offers_{$cafeId}");
    }
}
