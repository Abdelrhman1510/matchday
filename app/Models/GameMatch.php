<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameMatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'game_matches';

    protected $fillable = [
        'branch_id',
        'home_team_id',
        'away_team_id',
        'league',
        'match_date',
        'kick_off',
        'home_score',
        'away_score',
        'status',
        'seats_available',
        'price_per_seat',
        'duration_minutes',
        'total_revenue',
        'booking_opens_at',
        'booking_closes_at',
        'is_published',
        'is_live',
        'is_trending',
        'ticket_price',
        'field_name',
        'venue_name',
        'last_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'datetime',
            'kick_off' => 'datetime:H:i',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'seats_available' => 'integer',
            'price_per_seat' => 'decimal:2',
            'duration_minutes' => 'integer',
            'total_revenue' => 'decimal:2',
            'booking_opens_at' => 'datetime',
            'booking_closes_at' => 'datetime',
            'is_published' => 'boolean',
            'is_live' => 'boolean',
            'is_trending' => 'boolean',
            'ticket_price' => 'decimal:2',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'match_id');
    }

    public function chatRoom(): HasOne
    {
        return $this->hasOne(ChatRoom::class, 'match_id');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_matches', 'match_id', 'user_id')->withTimestamps();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('match_date', '>=', now()->toDateString())
            ->orderBy('match_date')
            ->orderBy('kick_off');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByLeague($query, string $league)
    {
        return $query->where('league', $league);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('match_date', now()->toDateString());
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('match_date', $date);
    }

    public function scopeAvailableForBooking($query)
    {
        return $query->where('is_published', true)
            ->where('status', 'upcoming')
            ->where('seats_available', '>', 0)
            ->where(function ($q) {
                $q->whereNull('booking_opens_at')
                  ->orWhere('booking_opens_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('booking_closes_at')
                  ->orWhere('booking_closes_at', '>=', now());
            });
    }
}
