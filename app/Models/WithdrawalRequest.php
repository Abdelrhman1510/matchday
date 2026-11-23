<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected $hidden = ['bank_snapshot'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'fee' => 'decimal:2', 'net_amount' => 'decimal:2', 'funds_reserved' => 'boolean', 'payout_blocked' => 'boolean', 'bank_snapshot' => 'encrypted:array', 'manual_transfer_date' => 'date', 'moyasar_checked_at' => 'datetime', 'reviewed_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function events(): HasMany { return $this->hasMany(WithdrawalEvent::class, 'withdrawal_id')->orderBy('id'); }
}
