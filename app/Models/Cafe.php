<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cafe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'logo',
        'description',
        'cafe_type',
        'cancellation_hours',
        'cancellation_policy',
        'phone',
        'website_url',
        'city',
        'is_premium',
        'is_featured',
        'avg_rating',
        'total_reviews',
        'subscription_plan',
        'payment_method_id',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'is_featured' => 'boolean',
            'avg_rating' => 'decimal:1',
            'total_reviews' => 'integer',
            'logo' => 'array',
            'cancellation_hours' => 'integer',
        ];
    }

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CafeSubscription::class);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_cafes')->withTimestamps();
    }

    // Scopes
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeHighRated($query, float $minRating = 4.0)
    {
        return $query->where('avg_rating', '>=', $minRating);
    }

}
