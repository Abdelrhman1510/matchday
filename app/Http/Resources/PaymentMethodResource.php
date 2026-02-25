<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'last4' => $this->card_last_four,
            'card_last_four' => $this->card_last_four,
            'card_holder' => $this->card_holder,
            'expires_at' => $this->expires_at,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
