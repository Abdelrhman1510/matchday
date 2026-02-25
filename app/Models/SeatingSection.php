<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeatingSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'type',
        'total_seats',
        'extra_cost',
        'icon',
        'screen_size',
    ];

    protected function casts(): array
    {
        return [
            'total_seats' => 'integer',
            'extra_cost' => 'decimal:2',
        ];
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'section_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVip($query)
    {
        return $query->where('type', 'vip');
    }

    public function scopePremium($query)
    {
        return $query->where('type', 'premium');
    }
}
