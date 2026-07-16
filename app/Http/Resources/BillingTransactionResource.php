<?php

namespace App\Http\Resources;

use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingTransactionResource extends JsonResource
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
            'title' => $this->getTitle(),
            'title_ar' => $this->getTitleAr(),
            'subtitle' => $this->getSubtitle(),
            'subtitle_ar' => $this->getSubtitleAr(),
            'type' => $this->type,
            'status' => strtoupper($this->status),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'currency_ar' => Currency::arabicName($this->currency),
            'date' => $this->created_at->format('Y-m-d'),
            'time' => $this->created_at->format('H:i:s'),
            'gateway_ref' => $this->gateway_ref,
            
            // UI Helper Flags
            'is_paid' => $this->status === 'paid',
            'is_pending' => $this->status === 'pending',
            'is_failed' => $this->status === 'failed',
            'is_refunded' => $this->status === 'refunded',
            'formatted_amount' => number_format($this->amount, 2) . ' ' . $this->currency,
            'formatted_date' => $this->created_at->format('M d, Y'),
            'status_color' => $this->getStatusColor(),
        ];
    }

    /**
     * Get transaction title based on type
     */
    private function getTitle(): string
    {
        switch ($this->type) {
            case 'subscription':
                return 'Subscription Payment';
            case 'booking':
                return 'Booking Payment';
            case 'cafe_order':
                return 'Cafe Order Payment';
            default:
                return 'Payment';
        }
    }

    /**
     * Arabic transaction title based on type.
     */
    private function getTitleAr(): string
    {
        switch ($this->type) {
            case 'subscription':
                return 'دفع الاشتراك';
            case 'booking':
                return 'دفع الحجز';
            case 'cafe_order':
                return 'دفع طلب المقهى';
            default:
                return 'دفعة';
        }
    }

    /**
     * Get transaction subtitle with details
     */
    private function getSubtitle(): string
    {
        switch ($this->type) {
            case 'subscription':
                return $this->description ?? 'Subscription renewal';
            case 'booking':
                if ($this->booking) {
                    $matchInfo = $this->booking->match 
                        ? $this->booking->match->home_team . ' vs ' . $this->booking->match->away_team 
                        : 'Match booking';
                    return $matchInfo . ' - ' . $this->booking->total_guests . ' guests';
                }
                return 'Booking payment';
            case 'cafe_order':
                return $this->description ?? 'Cafe order';
            default:
                return $this->description ?? '';
        }
    }

    /**
     * Arabic transaction subtitle with details.
     */
    private function getSubtitleAr(): string
    {
        switch ($this->type) {
            case 'subscription':
                return $this->description ?? 'تجديد الاشتراك';
            case 'booking':
                if ($this->booking) {
                    $matchInfo = $this->booking->match
                        ? $this->booking->match->home_team . ' ضد ' . $this->booking->match->away_team
                        : 'حجز مباراة';
                    return $matchInfo . ' - ' . $this->booking->total_guests . ' ضيوف';
                }
                return 'دفع الحجز';
            case 'cafe_order':
                return $this->description ?? 'طلب مقهى';
            default:
                return $this->description ?? '';
        }
    }

    /**
     * Get status color for UI
     */
    private function getStatusColor(): string
    {
        switch ($this->status) {
            case 'paid':
                return 'success';
            case 'pending':
                return 'warning';
            case 'failed':
                return 'error';
            case 'refunded':
                return 'info';
            default:
                return 'default';
        }
    }
}
