<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'currency',
        'features',
        'max_bookings',
        'has_analytics',
        'has_branding',
        'has_priority_support',
        'is_active',
        // New limit columns
        'max_branches',
        'max_matches_per_month',
        'max_bookings_per_month',
        'max_staff_members',
        'max_offers',
        // New feature flags
        'has_chat',
        'has_qr_scanner',
        'has_occupancy_tracking',
        // Commission override
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'max_bookings' => 'integer',
            'has_analytics' => 'boolean',
            'has_branding' => 'boolean',
            'has_priority_support' => 'boolean',
            'is_active' => 'boolean',
            // New casts
            'max_branches' => 'integer',
            'max_matches_per_month' => 'integer',
            'max_bookings_per_month' => 'integer',
            'max_staff_members' => 'integer',
            'max_offers' => 'integer',
            'has_chat' => 'boolean',
            'has_qr_scanner' => 'boolean',
            'has_occupancy_tracking' => 'boolean',
            'commission_rate' => 'decimal:2',
        ];
    }

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CafeSubscription::class, 'plan_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}
