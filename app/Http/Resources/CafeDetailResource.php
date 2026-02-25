<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CafeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if cafe is saved by authenticated user
        $isSaved = false;
        if ($request->user()) {
            $isSaved = $request->user()->savedCafes()->where('cafe_id', $this->id)->exists();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? url('storage/' . $this->logo) : null,
            'description' => $this->description,
            'phone' => $this->phone,
            'city' => $this->city,
            'is_premium' => $this->is_premium,
            'is_saved' => $isSaved,
            'avg_rating' => (float) $this->avg_rating,
            'total_reviews' => $this->total_reviews,
            'subscription_plan' => $this->subscription_plan,
            'branches_count' => $this->branches_count ?? $this->branches->count(),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
