<?php

namespace App\Livewire\Platform;

use App\Models\BankAccount;
use App\Models\SettingsAuditLog;
use App\Services\Wallet\BankAccountService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.platform', ['title' => 'Bank Accounts'])]
class BankAccountsPage extends Component
{
    use WithPagination;

    public string $status = 'pending_verification';
    public array $reasons = [];
    public array $revealed = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('withdrawals.view'), 403);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function verify(int $id, BankAccountService $banks): void
    {
        abort_unless(auth()->user()->can('withdrawals.approve'), 403);
        $banks->verify(BankAccount::findOrFail($id), auth()->user());
        session()->flash('message', __('finance.action_done'));
    }

    public function reject(int $id, BankAccountService $banks): void
    {
        abort_unless(auth()->user()->can('withdrawals.approve'), 403);
        $reason = trim($this->reasons[$id] ?? '');
        if ($reason === '') {
            $this->addError("reasons.{$id}", __('finance.reject_reason'));
            return;
        }
        $banks->reject(BankAccount::findOrFail($id), mb_substr($reason, 0, 255));
        unset($this->reasons[$id]);
        session()->flash('message', __('finance.action_done'));
    }

    /** Every reveal of a full IBAN is audited. */
    public function reveal(int $id): void
    {
        abort_unless(auth()->user()->can('finance.reveal_iban'), 403);
        $account = BankAccount::findOrFail($id);
        SettingsAuditLog::create([
            'key' => "iban_revealed.bank_account.{$account->id}",
            'new' => ['cafe_id' => $account->cafe_id],
            'user_id' => auth()->id(),
        ]);
        $this->revealed[$id] = $account->iban;
    }

    public function render()
    {
        return view('livewire.platform.bank-accounts-page', [
            'accounts' => BankAccount::with('cafe.owner')
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->latest('updated_at')
                ->paginate(20),
        ]);
    }
}
