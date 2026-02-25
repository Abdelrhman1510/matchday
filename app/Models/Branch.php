<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cafe_id',
        'name',
        'address',
        'phone',
        'city',
        'area',
        'latitude',
        'longitude',
        'total_seats',
        'pitches_count',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total_seats' => 'integer',
            'pitches_count' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    // Relationships
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(BranchHour::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'branch_amenity')->withTimestamps();
    }

    public function seatingSections(): HasMany
    {
        return $this->hasMany(SeatingSection::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_staff')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radius = 10)
    {
        // Simple distance calculation (for more accuracy, use spatial functions)
        return $query->selectRaw('*, 
            ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) 
            * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) 
            * sin( radians( latitude ) ) ) ) AS distance', [$lat, $lng, $lat])
            ->having('distance', '<', $radius)
            ->orderBy('distance');
    }

    // Computed Availability Status

    /**
     * Get occupancy percentage for this branch based on today's checked-in bookings.
     */
    public function getOccupancyPercentageAttribute(): float
    {
        if ($this->total_seats <= 0) {
            return 0;
        }

        $todayMatchIds = GameMatch::where('branch_id', $this->id)
            ->whereDate('match_date', now()->toDateString())
            ->whereIn('status', ['upcoming', 'live'])
            ->pluck('id');

        if ($todayMatchIds->isEmpty()) {
            return 0;
        }

        $currentGuests = Booking::whereIn('match_id', $todayMatchIds)
            ->where('status', 'checked_in')
            ->sum('guests_count');

        return round(($currentGuests / $this->total_seats) * 100, 1);
    }

    /**
     * Get current availability status: available, busy, full, or closed.
     */
    public function getCurrentStatusAttribute(): string
    {
        if (!$this->is_open) {
            return 'closed';
        }

        $occupancy = $this->occupancy_percentage;

        if ($occupancy > 90) {
            return 'full';
        }

        if ($occupancy >= 70) {
            return 'busy';
        }

        return 'available';
    }

    /**
     * Get status color for frontend rendering.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->current_status) {
            'available' => 'green',
            'busy' => 'orange',
            'full' => 'red',
            'closed' => 'gray',
            default => 'gray',
        };
    }
}
