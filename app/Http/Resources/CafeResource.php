<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CafeResource extends JsonResource
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

        // Compute aggregate availability status from branches
        $aggregateStatus = 'closed';
        if ($this->relationLoaded('branches') && $this->branches->isNotEmpty()) {
            $statuses = $this->branches->pluck('current_status')->toArray();
            if (in_array('available', $statuses)) {
                $aggregateStatus = 'available';
            } elseif (in_array('busy', $statuses)) {
                $aggregateStatus = 'busy';
            } elseif (in_array('full', $statuses)) {
                $aggregateStatus = 'full';
            }
        }

        $statusColorMap = [
            'available' => 'green',
            'busy' => 'orange',
            'full' => 'red',
            'closed' => 'gray',
        ];

        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? url('storage/' . $this->logo) : null,
            'description' => $this->description,
            'phone' => $this->phone,
            'city' => $this->city,
            'is_premium' => $this->is_premium,
            'avg_rating' => (float) $this->avg_rating,
            'total_reviews' => $this->total_reviews,
            'branches_count' => $this->branches_count ?? $this->branches->count(),
            'is_saved' => $isSaved,
            'current_status' => $aggregateStatus,
            'status_color' => $statusColorMap[$aggregateStatus],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Add distance if available (from nearby query)
        if ($this->relationLoaded('branches') && $this->branches->first() && isset($this->branches->first()->distance)) {
            $result['distance'] = round($this->branches->first()->distance, 2);
        }

        return $result;
    }
}
