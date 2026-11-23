<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $table = 'wallets';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function cafe(): BelongsTo { return $this->belongsTo(Cafe::class); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class); }
    public function withdrawals(): HasMany { return $this->hasMany(WithdrawalRequest::class); }
}
