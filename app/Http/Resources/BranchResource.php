<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'address' => $this->address,
            'area' => $this->area,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'total_seats' => $this->total_seats,
            'is_open' => $this->is_open,
            'current_status' => $this->current_status,
            'status_color' => $this->status_color,
            'distance' => $this->when(isset($this->distance), function () {
                return round($this->distance, 2) . ' km';
            }),
            'cafe' => $this->when($this->relationLoaded('cafe'), function () {
                // Handle logo - can be array (multi-size) or string (legacy)
                $logo = null;
                if ($this->cafe->logo) {
                    $logo = is_array($this->cafe->logo) 
                        ? $this->cafe->logo 
                        : url('storage/' . $this->cafe->logo);
                }
                
                return [
                    'id' => $this->cafe->id,
                    'name' => $this->cafe->name,
                    'logo' => $logo,
                ];
            }),
            'hours' => BranchHourResource::collection($this->whenLoaded('hours')),
            'amenities' => BranchAmenityResource::collection($this->whenLoaded('amenities')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
