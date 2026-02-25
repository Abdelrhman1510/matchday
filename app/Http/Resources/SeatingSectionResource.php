<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatingSectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $occupiedSeats = $this->seats()
            ->whereHas('bookings', function ($query) {
                $query->where('status', 'confirmed')
                      ->whereDate('booking_date', now()->toDateString());
            })
            ->count();

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'type' => $this->type ?? $this->section_type,
            'total_seats' => $this->total_seats,
            'extra_cost' => (float) ($this->extra_cost ?? $this->price_multiplier ?? 0),
            'icon' => $this->icon,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Occupancy info (when seats are loaded)
            'occupied_seats' => $this->when($this->relationLoaded('seats'), $occupiedSeats),
            'available_seats' => $this->when($this->relationLoaded('seats'), $this->total_seats - $occupiedSeats),
            'occupancy_percentage' => $this->when(
                $this->relationLoaded('seats') && $this->total_seats > 0,
                round(($occupiedSeats / $this->total_seats) * 100, 2)
            ),
            
            // Relationships
            'seats' => SeatResource::collection($this->whenLoaded('seats')),
        ];
    }
}
