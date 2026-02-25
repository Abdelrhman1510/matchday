<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingAdminDetailResource extends JsonResource
{
    /**
     * The resource wraps a custom array: booking, is_returning
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource['booking'];
        $isReturning = $this->resource['is_returning'] ?? false;

        $loyaltyTier = $booking->user?->loyaltyCard?->tier ?? 'bronze';
        $isVip = in_array($loyaltyTier, ['gold', 'platinum']);

        $seatNumbers = $booking->seats->map(fn($seat) => $seat->label)->values()->toArray();

        return [
            'id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'status' => $booking->status,
            'guests_count' => $booking->guests_count,
            'special_requests' => $booking->special_requests,
            'qr_code' => $booking->qr_code,
            'created_at' => $booking->created_at?->toIso8601String(),
            'checked_in_at' => $booking->checked_in_at?->toIso8601String(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601String(),

            // Customer details
            'user' => [
                'id' => $booking->user->id,
                'name' => $booking->user->name,
                'phone' => $booking->user->phone,
                'email' => $booking->user->email,
                'avatar' => $this->formatAvatar($booking->user->avatar),
                'loyalty_tier' => $loyaltyTier,
                'loyalty_points' => $booking->user->loyaltyCard?->points ?? 0,
            ],

            // Match
            'match' => $booking->match ? [
                'id' => $booking->match->id,
                'league' => $booking->match->league,
                'match_date' => $booking->match->match_date?->format('Y-m-d'),
                'kick_off' => $booking->match->kick_off,
                'status' => $booking->match->status,
                'branch' => $booking->match->branch ? [
                    'id' => $booking->match->branch->id,
                    'name' => $booking->match->branch->name,
                ] : null,
                'home_team' => $booking->match->homeTeam ? [
                    'id' => $booking->match->homeTeam->id,
                    'name' => $booking->match->homeTeam->name,
                    'short_name' => $booking->match->homeTeam->short_name,
                    'logo' => $this->formatTeamLogo($booking->match->homeTeam->logo),
                ] : null,
                'away_team' => $booking->match->awayTeam ? [
                    'id' => $booking->match->awayTeam->id,
                    'name' => $booking->match->awayTeam->name,
                    'short_name' => $booking->match->awayTeam->short_name,
                    'logo' => $this->formatTeamLogo($booking->match->awayTeam->logo),
                ] : null,
            ] : null,

            // Seats with seat_numbers array
            'seat_numbers' => $seatNumbers,
            'seats' => $booking->seats->map(fn($seat) => [
                'id' => $seat->id,
                'label' => $seat->label,
                'table_number' => $seat->table_number,
                'section' => $seat->section?->name ?? null,
            ])->toArray(),

            // Payment summary
            'payment' => $this->buildPaymentSummary($booking),
            'payment_summary' => $this->buildPaymentSummary($booking),

            // UI helper flags
            'is_vip' => $isVip,
            'is_returning' => $isReturning,
            'can_check_in' => $booking->status === 'confirmed',
            'can_cancel' => in_array($booking->status, ['confirmed', 'pending']),
            'is_checked_in' => $booking->status === 'checked_in',
        ];
    }

    private function buildPaymentSummary($booking): array
    {
        $seatCount = $booking->seats->count();
        $pricePerSeat = $booking->match?->price_per_seat ?? 0;
        $seatTotal = (float) $booking->subtotal;

        $paymentMethodDisplay = null;
        if ($booking->payment?->paymentMethod) {
            $pm = $booking->payment->paymentMethod;
            $type = str_replace('_', ' ', ucfirst($pm->type ?? 'card'));
            $type = ucwords($type);
            $last4 = $pm->card_last_four ?? '****';
            $paymentMethodDisplay = "{$type} ••••{$last4}";
        }

        return [
            'seat_price_unit' => (float) $pricePerSeat,
            'seat_count' => $seatCount,
            'seat_total' => $seatTotal,
            'service_fee' => (float) $booking->service_fee,
            'total' => (float) $booking->total_amount,
            'payment_status' => $booking->payment?->status ?? 'unknown',
            'payment_method_display' => $paymentMethodDisplay,
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
}
