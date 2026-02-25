<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchListResource extends JsonResource
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
            'total_seats' => $this->total_seats,
            'is_open' => $this->is_open,
            'created_at' => $this->created_at?->toISOString(),
            
            // UI Helper Flags
            'status_badge' => $this->is_open ? 'ACTIVE' : 'CLOSED',
            'current' => $this->when(
                isset($request->currentBranchId),
                fn() => $this->id === $request->currentBranchId
            ),
            
            // Stats (when loaded)
            'stats' => $this->when($this->relationLoaded('bookings') || $this->relationLoaded('seatingSections'), function () {
                return [
                    'pitches_count' => $this->seatingSections()->count(),
                    'active_bookings_count' => $this->bookings()
                        ->where('status', 'confirmed')
                        ->whereDate('booking_date', '>=', now())
                        ->count(),
                    'rating' => (float) ($this->reviews()->avg('rating') ?? 0),
                ];
            }),
        ];
    }
}
