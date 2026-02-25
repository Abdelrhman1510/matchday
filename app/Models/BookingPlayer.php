<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'name',
        'position',
        'jersey_number',
        'is_captain',
    ];

    protected function casts(): array
    {
        return [
            'is_captain' => 'boolean',
        ];
    }

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCaptains($query)
    {
        return $query->where('is_captain', true);
    }
}
