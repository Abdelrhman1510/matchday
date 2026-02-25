<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedCafeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? url('storage/' . $this->logo) : null,
            'description' => $this->description,
            'city' => $this->city,
            'avg_rating' => (float) $this->avg_rating,
            'total_reviews' => $this->total_reviews,
            'branches_count' => $this->branches_count ?? 0,
            'saved_at' => $this->pivot?->created_at?->toISOString(),
        ];
    }
}
