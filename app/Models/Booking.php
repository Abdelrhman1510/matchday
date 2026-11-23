<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'user_id',
        'match_id',
        'branch_id',
        'guests_count',
        'status',
        'payment_status',
        'payment_method',
        'payment_expires_at',
        'paid_at',
        'cancel_reason',
        'commission_amount',
        'gateway_fee',
        'cafe_net_amount',
        'fees_snapshot',
        'special_requests',
        'subtotal',
        'service_fee',
        'total_amount',
        'currency',
        'qr_code',
        'checked_in_at',
        'cancelled_at',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'guests_count' => 'integer',
            'subtotal' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'commission_amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'cafe_net_amount' => 'decimal:2',
            'fees_snapshot' => 'array',
        ];
    }

    /**
     * A booking the customer is really coming to. A pay-at-cafe booking stays
     * `pending` until check-in but is as good as confirmed for everything else.
     * Works on Eloquent and query-builder queries over the bookings table.
     */
    public static function activeConstraint(bool $includeCheckedIn = true): \Closure
    {
        $statuses = $includeCheckedIn ? ['confirmed', 'checked_in'] : ['confirmed'];

        return function ($query) use ($statuses) {
            $query->whereIn('bookings.status', $statuses)
                ->orWhere(function ($q) {
                    $q->where('bookings.status', 'pending')->where('bookings.payment_method', 'pay_at_cafe');
                });
        };
    }

    /** The customer's unexpired unpaid booking for this match, if any. */
    public static function openPendingFor(int $userId, int $matchId): ?self
    {
        return static::where('user_id', $userId)
            ->where('match_id', $matchId)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('payment_expires_at')->orWhere('payment_expires_at', '>', now());
            })
            ->first();
    }

    public function isPayAtCafePending(): bool
    {
        return $this->status === 'pending' && $this->payment_method === 'pay_at_cafe';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['confirmed', 'checked_in'], true) || $this->isPayAtCafePending();
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->user();
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function seats(): BelongsToMany
    {
        return $this->belongsToMany(Seat::class, 'booking_seats')->withTimestamps();
    }

    public function players(): HasMany
    {
        return $this->hasMany(BookingPlayer::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    /** Bookings whose match is still to come or in progress (dates are Riyadh time). */
    public function scopeForOpenMatch($query)
    {
        return $query->whereHas('match', function ($q) {
            $q->whereDate('match_date', '>=', now('Asia/Riyadh')->toDateString())
                ->whereNotIn('status', ['finished', 'cancelled']);
        });
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereIn('status', ['confirmed', 'pending'])
            ->whereHas('match', function ($q) {
                $q->where('match_date', '>=', now()->toDateString());
            });
    }
}
