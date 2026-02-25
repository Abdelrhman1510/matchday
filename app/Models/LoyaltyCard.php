<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_number',
        'points',
        'tier',
        'total_points_earned',
        'issued_date',
    ];

    protected $hidden = ['user'];  // Prevent circular reference

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'total_points_earned' => 'integer',
            'issued_date' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    // Scopes
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeBronze($query)
    {
        return $query->where('tier', 'bronze');
    }

    public function scopeSilver($query)
    {
        return $query->where('tier', 'silver');
    }

    public function scopeGold($query)
    {
        return $query->where('tier', 'gold');
    }

    public function scopePlatinum($query)
    {
        return $query->where('tier', 'platinum');
    }
}
