<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatingSectionAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $seatsCount = $this->seats_count ?? $this->seats()->count();
        $availableCount = $this->available_seats_count ?? $this->seats()->where('is_available', true)->count();
        $occupiedCount = $this->occupied_seats_count ?? $this->seats()->where('is_available', false)->count();
        $activeBookings = $this->active_bookings_count ?? 0;

        $result = [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'total_seats' => $this->total_seats,
            'extra_cost' => (float) $this->extra_cost,
            'icon' => $this->icon,
            'screen_size' => $this->screen_size,

            // Counts
            'seats_count' => $seatsCount,
            'available_seats' => $availableCount,
            'occupied_seats' => $occupiedCount,
            'active_bookings' => $activeBookings,

            // UI Flags
            'can_delete' => $activeBookings === 0,
            'has_active_bookings' => $activeBookings > 0,
            'is_full' => $availableCount === 0 && $seatsCount > 0,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include seats if the relation is loaded
        if ($this->relationLoaded('seats')) {
            $result['seats'] = SeatAdminResource::collection($this->seats);
        }

        return $result;
    }

    /**
     * Get human-readable type label
     */
    protected function getTypeLabel(): string
    {
        return match ($this->type) {
            'main_screen' => 'Main Screen',
            'vip' => 'VIP',
            'premium' => 'Premium',
            'standard' => 'Standard',
            default => ucfirst($this->type),
        };
    }
}
