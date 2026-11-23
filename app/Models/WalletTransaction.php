<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'available_at' => 'datetime'];
    }

    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function withdrawal(): BelongsTo { return $this->belongsTo(WithdrawalRequest::class, 'withdrawal_id'); }
}
