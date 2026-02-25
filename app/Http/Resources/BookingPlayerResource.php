<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingPlayerResource extends JsonResource
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
            'jersey_number' => $this->jersey_number,
            'position' => $this->position,
            'is_captain' => $this->is_captain,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
