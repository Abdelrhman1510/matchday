<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
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
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'guests_count' => $this->guests_count,
            'special_requests' => $this->special_requests,
            'subtotal' => (float) $this->subtotal,
            'service_fee' => (float) $this->service_fee,
            'total_amount' => (float) $this->total_amount,
            'total_price' => (float) $this->total_amount,
            'currency' => $this->currency,
            'qr_code' => $this->qr_code,
            'match' => [
                'id' => $this->match->id,
                'league' => $this->match->league,
                'match_date' => $this->match->match_date->format('Y-m-d'),
                'kick_off' => $this->match->kick_off,
                'status' => $this->match->status,
                'home_team' => [
                    'id' => $this->match->homeTeam->id,
                    'name' => $this->match->homeTeam->name,
                    'logo' => $this->match->homeTeam->logo,
                    'country' => $this->match->homeTeam->country,
                ],
                'away_team' => [
                    'id' => $this->match->awayTeam->id,
                    'name' => $this->match->awayTeam->name,
                    'logo' => $this->match->awayTeam->logo,
                    'country' => $this->match->awayTeam->country,
                ],
                'home_score' => $this->match->home_score,
                'away_score' => $this->match->away_score,
                'price_per_seat' => (float) $this->match->price_per_seat,
            ],
            'branch' => [
                'id' => $this->match->branch->id,
                'name' => $this->match->branch->name,
                'address' => $this->match->branch->address,
                'phone' => $this->match->branch->phone,
                'cafe' => [
                    'id' => $this->match->branch->cafe->id,
                    'name' => $this->match->branch->cafe->name,
                    'logo' => $this->match->branch->cafe->logo,
                    'description' => $this->match->branch->cafe->description,
                ],
            ],
            'seats' => $this->seats->map(function ($seat) {
                return [
                    'id' => $seat->id,
                    'label' => $seat->label,
                    'section' => [
                        'id' => $seat->section->id,
                        'name' => $seat->section->name,
                        'type' => $seat->section->type,
                        'extra_cost' => (float) ($seat->section->extra_cost ?? 0),
                    ],
                ];
            }),
            'players' => BookingPlayerResource::collection($this->whenLoaded('players')),
            'payment' => $this->when($this->payment, function () {
                return [
                    'id' => $this->payment->id,
                    'amount' => (float) $this->payment->amount,
                    'status' => $this->payment->status,
                    'method' => $this->payment->paymentMethod?->name ?? null,
                    'paid_at' => $this->payment->paid_at?->toIso8601String(),
                ];
            }),
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
