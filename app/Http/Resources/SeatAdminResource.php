<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasActiveBooking = $this->has_active_booking ?? false;

        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'label' => $this->label,
            'table_number' => $this->table_number,
            'price' => $this->price ? (float) $this->price : null,
            'is_available' => $this->is_available,
            'status' => $this->getStatus($hasActiveBooking),

            // UI Flags
            'can_delete' => !$hasActiveBooking,
            'has_active_booking' => $hasActiveBooking,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Determine seat status
     */
    protected function getStatus(bool $hasActiveBooking): string
    {
        if ($hasActiveBooking) {
            return 'booked';
        }

        return $this->is_available ? 'available' : 'unavailable';
    }
}
