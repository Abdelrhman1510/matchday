<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'type' => $this->type,
            'description' => $this->description,
            'gateway_ref' => $this->gateway_ref,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            
            // Relationships
            'booking' => $this->when($this->relationLoaded('booking') && $this->booking, function () {
                return [
                    'id' => $this->booking->id,
                    'booking_code' => $this->booking->booking_code,
                    'status' => $this->booking->status,
                    'match' => $this->when($this->booking->relationLoaded('match'), function () {
                        return [
                            'id' => $this->booking->match->id,
                            'league' => $this->booking->match->league,
                            'home_team' => $this->booking->match->relationLoaded('homeTeam') ? $this->booking->match->homeTeam->name : null,
                            'away_team' => $this->booking->match->relationLoaded('awayTeam') ? $this->booking->match->awayTeam->name : null,
                            'match_date' => $this->booking->match->match_date->toDateString(),
                            'kick_off' => $this->booking->match->kick_off->format('H:i'),
                        ];
                    }),
                ];
            }),
            
            'payment_method' => $this->when($this->relationLoaded('paymentMethod') && $this->paymentMethod, function () {
                return [
                    'id' => $this->paymentMethod->id,
                    'type' => $this->paymentMethod->type,
                    'card_last_four' => $this->paymentMethod->card_last_four,
                ];
            }),
        ];
    }
}
