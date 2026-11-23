<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    // Written only by the wallet services, never mass-assigned from a request.
    protected $guarded = ['id'];

    protected $hidden = ['iban'];

    protected function casts(): array
    {
        return ['iban' => 'encrypted', 'verified_at' => 'datetime'];
    }

    public function cafe(): BelongsTo { return $this->belongsTo(Cafe::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function getIbanMaskedAttribute(): string
    {
        return 'SA•• •••• •••• •••• •••• ' . $this->iban_last4;
    }
}
