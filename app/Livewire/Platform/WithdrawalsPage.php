<?php

namespace App\Livewire\Platform;

use App\Exceptions\WalletException;
use App\Models\WalletTransaction;
use App\Models\WithdrawalEvent;
use App\Models\WithdrawalRequest;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.platform', ['title' => 'Withdrawal Requests'])]
class WithdrawalsPage extends Component
{
    use WithFileUploads, WithPagination;

    public string $status = 'pending';
    public string $cafe = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $amountMin = '';
    public string $amountMax = '';

    public ?int $selectedId = null;
    public ?string $revealedIban = null;
    public string $rejectReason = '';
    public string $transferRef = '';
    public string $transferDate = '';
    public $receipt = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('withdrawals.view'), 403);
        $this->transferDate = now()->toDateString();
    }

    public function updating($name): void
    {
        if (in_array($name, ['status', 'cafe', 'dateFrom', 'dateTo', 'amountMin', 'amountMax'], true)) {
            $this->resetPage();
        }
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
        $this->reset('revealedIban', 'rejectReason', 'transferRef', 'receipt');
    }

    public function closeDetail(): void
    {
        $this->reset('selectedId', 'revealedIban');
    }

    /** Every reveal of a full IBAN is written to the request's timeline. */
    public function revealIban(): void
    {
        abort_unless(auth()->user()->can('finance.reveal_iban'), 403);
        $request = WithdrawalRequest::findOrFail($this->selectedId);
        WithdrawalEvent::create([
            'withdrawal_id' => $request->id, 'user_id' => auth()->id(), 'action' => 'iban_revealed',
            'from_status' => $request->status, 'to_status' => $request->status, 'created_at' => now(),
        ]);
        $this->revealedIban = $request->bank_snapshot['iban'] ?? null;
    }

    public function approve(WithdrawalService $service): void
    {
        $this->act(fn ($r) => $service->approveAndSend($r, auth()->user()));
    }

    public function reject(WithdrawalService $service): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:500']]);
        $this->act(fn ($r) => $service->reject($r, auth()->user(), $this->rejectReason));
    }

    public function markPaid(WithdrawalService $service): void
    {
        $this->validate([
            'transferRef' => ['required', 'string', 'max:100'],
            'transferDate' => ['required', 'date', 'before_or_equal:today'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        // Private disk: receipts contain bank details.
        $path = $this->receipt?->store('withdrawal-receipts', 'local');
        $this->act(fn ($r) => $service->markPaidManually($r, auth()->user(), $this->transferRef, $this->transferDate, $path));
    }

    public function retry(WithdrawalService $service): void
    {
        $this->act(fn ($r) => $service->retry($r, auth()->user()));
    }

    public function markReturned(WithdrawalService $service): void
    {
        $this->act(fn ($r) => $service->markReturned($r, auth()->user(), $this->rejectReason ?: null));
    }

    private function act(callable $action): void
    {
        abort_unless(auth()->user()->can('withdrawals.approve'), 403);

        try {
            $action(WithdrawalRequest::findOrFail($this->selectedId));
            session()->flash('message', __('finance.action_done'));
            $this->reset('rejectReason', 'transferRef', 'receipt');
        } catch (WalletException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function query()
    {
        return WithdrawalRequest::with(['wallet.cafe.owner', 'reviewer:id,name'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->cafe !== '', fn ($q) => $q->whereHas('wallet.cafe', fn ($c) => $c->where('name', 'like', "%{$this->cafe}%")))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when(is_numeric($this->amountMin), fn ($q) => $q->where('amount', '>=', (float) $this->amountMin))
            ->when(is_numeric($this->amountMax), fn ($q) => $q->where('amount', '<=', (float) $this->amountMax));
    }

    public function exportCsv()
    {
        abort_unless(auth()->user()->can('withdrawals.view'), 403);
        $rows = $this->query()->orderByDesc('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Reference', 'Cafe', 'Owner', 'Phone', 'Amount', 'Fee', 'Net', 'Bank', 'IBAN', 'Status', 'Requested at', 'Reviewed by']);
            foreach ($rows as $w) {
                fputcsv($out, array_map([self::class, 'csvSafe'], [
                    $w->reference, $w->wallet->cafe?->name, $w->wallet->cafe?->owner?->name, $w->wallet->cafe?->owner?->phone,
                    $w->amount, $w->fee, $w->net_amount, $w->bank_snapshot['bank_name'] ?? '', $w->bank_snapshot['iban_masked'] ?? '',
                    $w->status, $w->created_at, $w->reviewer?->name,
                ]));
            }
            fclose($out);
        }, 'withdrawals-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Cafe names are user input: stop them running as spreadsheet formulas. */
    public static function csvSafe($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    public function render(WalletService $wallet)
    {
        $selected = $this->selectedId
            ? WithdrawalRequest::with(['wallet.cafe.owner', 'events.user:id,name', 'reviewer:id,name'])->find($this->selectedId)
            : null;

        return view('livewire.platform.withdrawals-page', [
            'requests' => $this->query()->orderByDesc('id')->paginate(20),
            'totals' => [
                'pending_count' => WithdrawalRequest::where('status', 'pending')->count(),
                'pending_amount' => (float) WithdrawalRequest::where('status', 'pending')->sum('amount'),
                'paid_month' => (float) WithdrawalRequest::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('net_amount'),
            ],
            'selected' => $selected,
            'balances' => $selected ? $wallet->balances($selected->wallet) : null,
            'balanceAtRequest' => $selected ? $this->availableAt($selected) : null,
            'ledger' => $selected
                ? WalletTransaction::where('wallet_id', $selected->wallet_id)->latest('id')->limit(20)->get()
                : collect(),
            'previous' => $selected
                ? WithdrawalRequest::where('wallet_id', $selected->wallet_id)->whereKeyNot($selected->id)->latest('id')->limit(10)->get()
                : collect(),
        ]);
    }

    /** Available balance just before this request's own debits were written. */
    private function availableAt(WithdrawalRequest $request): float
    {
        $firstDebitId = WalletTransaction::where('withdrawal_id', $request->id)->min('id');

        $row = WalletTransaction::where('wallet_id', $request->wallet_id)
            ->where('status', 'available')
            ->when($firstDebitId, fn ($q) => $q->where('id', '<', $firstDebitId))
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) as total")
            ->first();

        return round((float) $row->total, 2);
    }
}
