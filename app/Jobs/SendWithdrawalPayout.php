<?php

namespace App\Jobs;

use App\Services\Wallet\WithdrawalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWithdrawalPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $withdrawalId) {}

    public function handle(WithdrawalService $withdrawals): void
    {
        $withdrawals->sendPayout($this->withdrawalId);
    }
}
