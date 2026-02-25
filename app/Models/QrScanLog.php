<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_id',
        'scanned_by',
        'booking_id',
        'booking_code',
        'result',
        'error_message',
        'processing_ms',
    ];

    protected function casts(): array
    {
        return [
            'processing_ms' => 'integer',
        ];
    }

    // Relationships
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeSuccessful($query)
    {
        return $query->where('result', 'success');
    }

    public function scopeForCafe($query, int $cafeId)
    {
        return $query->where('cafe_id', $cafeId);
    }
}
