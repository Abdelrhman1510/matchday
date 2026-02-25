<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $threeDaysFromNow = now()->addDays(3);
        $isExpiringSoon = $this->valid_until && 
                          $this->valid_until->lte($threeDaysFromNow);

        // Format image URLs (multi-size support)
        $image = null;
        if ($this->image && is_array($this->image)) {
            $image = [
                'original' => url('storage/' . $this->image['original']),
                'medium' => url('storage/' . $this->image['medium']),
                'thumbnail' => url('storage/' . $this->image['thumbnail']),
            ];
        }

        return [
            'id' => $this->id,
            'cafe_id' => $this->cafe_id,
            'cafe_name' => $this->whenLoaded('cafe', fn() => $this->cafe->name),
            'cafe_logo' => $this->whenLoaded('cafe', fn() => $this->cafe->logo),
            'cafe_city' => $this->whenLoaded('cafe', fn() => $this->cafe->city),
            'title' => $this->title,
            'description' => $this->description,
            'image' => $image,
            'original_price' => (float) $this->original_price,
            'offer_price' => (float) $this->offer_price,
            'discount_percent' => $this->discount_percent,
            'type' => $this->type,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'valid_until' => $this->valid_until?->format('Y-m-d'),
            'available_for' => $this->available_for,
            'terms' => $this->terms,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at->toIso8601String(),
            
            // UI helper flags
            'is_expiring_soon' => $isExpiringSoon,
            'days_remaining' => $this->valid_until ? (int)now()->diffInDays($this->valid_until, false) : null,
            'is_active' => $this->status === 'active',
            'type_label' => match($this->type) {
                'percentage' => 'Percentage Discount',
                'bogo' => 'Buy One Get One',
                'free_item' => 'Free Item',
                default => ucfirst($this->type),
            },
        ];
    }
}
