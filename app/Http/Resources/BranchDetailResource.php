<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchDetailResource extends JsonResource
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
            'cafe_id' => $this->cafe_id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'phone' => $this->phone,
            'city' => $this->city,
            'area' => $this->area,
            'total_seats' => $this->total_seats,
            'is_open' => $this->is_open,
            'current_status' => $this->current_status,
            'status_color' => $this->status_color,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // UI Helper Flags
            'status_badge' => $this->is_open ? 'ACTIVE' : 'CLOSED',
            'can_delete' => $this->bookings()->where('status', 'confirmed')->count() === 0,
            'has_hours_configured' => $this->hours()->count() >= 7,
            'has_amenities' => $this->amenities()->count() > 0,
            'has_seating' => $this->seatingSections()->count() > 0,
            
            // Relationships
            'cafe' => $this->when($this->relationLoaded('cafe'), function () {
                // Handle logo - can be array (multi-size) or string (legacy)
                $logo = null;
                if ($this->cafe->logo) {
                    if (is_array($this->cafe->logo)) {
                        $logo = $this->cafe->logo; // Already an array of sizes
                    } else {
                        $logo = url('storage/' . $this->cafe->logo);
                    }
                }
                
                return [
                    'id' => $this->cafe->id,
                    'name' => $this->cafe->name,
                    'logo' => $logo,
                    'phone' => $this->cafe->phone,
                    'city' => $this->cafe->city,
                    'avg_rating' => (float) $this->cafe->avg_rating,
                ];
            }),
            'hours' => BranchHourResource::collection($this->whenLoaded('hours')),
            'amenities' => BranchAmenityResource::collection($this->whenLoaded('amenities')),
            'seating_sections' => SeatingSectionResource::collection($this->whenLoaded('seatingSections')),
            'current_matches' => MatchResource::collection($this->whenLoaded('matches')),
        ];
    }
}
