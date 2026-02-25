<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cafe_id', 'branch_id', 'title', 'description', 'image',
        'original_price', 'offer_price', 'discount_percent',
        'discount_value', 'discount', 'discount_type',
        'type', 'status', 'is_featured', 'is_active',
        'valid_from', 'valid_until', 'start_date', 'end_date',
        'available_for', 'terms', 'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'original_price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'discount_percent' => 'integer',
            'discount_value' => 'decimal:2',
            'discount' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'usage_count' => 'integer',
            'image' => 'array',
        ];
    }

    // Relationships
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('valid_until', '<', now()->toDateString());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
