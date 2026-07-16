<?php

namespace App\Http\Resources;

use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
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
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'currency_ar' => Currency::arabicName($this->currency),
            'features' => $this->features,
            'features_ar' => $this->features_ar,
            'max_bookings' => $this->max_bookings,
            'has_analytics' => $this->has_analytics,
            'has_branding' => $this->has_branding,
            'has_priority_support' => $this->has_priority_support,
            'billing_period' => 'monthly',
            
            // UI Helper Flags
            'is_most_popular' => $this->slug === 'pro',
            'display_price' => $this->price . ' ' . $this->currency . '/month',
            'bookings_label' => $this->max_bookings 
                ? 'Up to ' . $this->max_bookings . ' bookings/month' 
                : 'Unlimited bookings',
        ];
    }
}
