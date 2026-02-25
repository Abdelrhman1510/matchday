<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingAdminResource extends JsonResource
{
    /**
     * Additional data passed to the resource.
     */
    protected array $returningUserIds = [];

    /**
     * Set returning user IDs for is_returning flag.
     */
    public static function collectionWithReturning($resource, array $returningUserIds)
    {
        return $resource->map(function ($booking) use ($returningUserIds) {
            return (new static($booking))->setReturningUserIds($returningUserIds);
        });
    }

    public function setReturningUserIds(array $ids): self
    {
        $this->returningUserIds = $ids;
        return $this;
    }

    public function toArray(Request $request): array
    {
        $loyaltyTier = $this->user?->loyaltyCard?->tier ?? 'bronze';
        $isVip = in_array($loyaltyTier, ['gold', 'platinum']);
        $isReturning = in_array($this->user_id, $this->returningUserIds);

        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'guests_count' => $this->guests_count,
            'created_at' => $this->created_at?->toIso8601String(),

            // Customer
            'user' => $this->when($this->relationLoaded('user'), fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
                'avatar' => $this->formatAvatar($this->user->avatar),
            ]),

            // Match
            'match' => $this->when($this->relationLoaded('match') && $this->match, fn() => [
                'id' => $this->match->id,
                'league' => $this->match->league,
                'match_date' => $this->match->match_date?->format('Y-m-d'),
                'kick_off' => $this->match->kick_off,
                'status' => $this->match->status,
                'home_team' => $this->when($this->match->relationLoaded('homeTeam'), fn() => [
                    'id' => $this->match->homeTeam->id,
                    'name' => $this->match->homeTeam->name,
                    'short_name' => $this->match->homeTeam->short_name,
                    'logo' => $this->formatTeamLogo($this->match->homeTeam->logo),
                ]),
                'away_team' => $this->when($this->match->relationLoaded('awayTeam'), fn() => [
                    'id' => $this->match->awayTeam->id,
                    'name' => $this->match->awayTeam->name,
                    'short_name' => $this->match->awayTeam->short_name,
                    'logo' => $this->formatTeamLogo($this->match->awayTeam->logo),
                ]),
            ]),

            // Seats summary
            'seats' => $this->when($this->relationLoaded('seats'), fn() =>
                $this->seats->map(fn($seat) => [
                    'id' => $seat->id,
                    'label' => $seat->label,
                    'section' => $seat->section?->name ?? null,
                ])->toArray()
            ),

            // Payment summary
            'payment' => $this->when($this->relationLoaded('payment') && $this->payment, fn() => [
                'amount' => (float) $this->payment->amount,
                'status' => $this->payment->status,
                'method' => $this->formatPaymentMethodShort($this->payment->paymentMethod),
            ]),

            // Amounts
            'subtotal' => (float) $this->subtotal,
            'service_fee' => (float) $this->service_fee,
            'total_amount' => (float) $this->total_amount,

            // UI helper flags
            'is_vip' => $isVip,
            'is_returning' => $isReturning,
            'can_check_in' => $this->status === 'confirmed',
            'can_cancel' => in_array($this->status, ['confirmed', 'pending']),
            'is_checked_in' => $this->status === 'checked_in',
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
        ];
    }

    private function formatTeamLogo($logo): ?array
    {
        if (!$logo) return null;
        if (is_array($logo)) {
            return [
                'original' => $logo['original'] ?? $logo['url'] ?? null,
                'medium' => $logo['medium'] ?? $logo['original'] ?? $logo['url'] ?? null,
                'thumbnail' => $logo['thumbnail'] ?? $logo['original'] ?? $logo['url'] ?? null,
            ];
        }
        return ['original' => $logo, 'medium' => $logo, 'thumbnail' => $logo];
    }

    private function formatAvatar($avatar): ?array
    {
        if (!$avatar) return null;
        if (is_array($avatar)) {
            return [
                'original' => $avatar['original'] ?? $avatar['url'] ?? null,
                'medium' => $avatar['medium'] ?? $avatar['original'] ?? $avatar['url'] ?? null,
                'thumbnail' => $avatar['thumbnail'] ?? $avatar['original'] ?? $avatar['url'] ?? null,
            ];
        }
        return ['original' => $avatar, 'medium' => $avatar, 'thumbnail' => $avatar];
    }

    private function formatPaymentMethodShort($method): ?string
    {
        if (!$method) return null;
        $type = str_replace('_', ' ', ucfirst($method->type ?? 'card'));
        $type = ucwords($type);
        $last4 = $method->card_last_four ?? '****';
        return "{$type} ••••{$last4}";
    }
}
