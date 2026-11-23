<?php

namespace App\Http\Controllers;

use App\Exceptions\WalletException;
use App\Models\BankAccount;
use App\Models\Cafe;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\Wallet\BankAccountService;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Cafe-owner wallet API (owner role only, enforced by the wallet.owner middleware). */
class CafeWalletController extends Controller
{
    public function __construct(
        private WalletService $wallet,
        private WithdrawalService $withdrawals,
        private BankAccountService $banks,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return $this->ok($this->withdrawals->overview($this->cafe($request)));
    }

    public function transactions(Request $request): JsonResponse
    {
        $page = WalletTransaction::where('wallet_id', $this->wallet->walletFor($this->cafe($request))->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($page, fn (WalletTransaction $t) => [
            'id' => $t->id,
            'type' => $t->type,
            'direction' => $t->direction,
            'amount' => (float) $t->amount,
            'status' => $t->status,
            'title' => $t->title,
            'title_ar' => $t->title_ar,
            'description' => $t->description,
            'description_ar' => $t->description_ar,
            'reference' => $t->reference,
            'created_at' => $t->created_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'available_at' => $t->available_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    public function withdrawalsIndex(Request $request): JsonResponse
    {
        $page = WithdrawalRequest::where('wallet_id', $this->wallet->walletFor($this->cafe($request))->id)
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($page, fn (WithdrawalRequest $w) => $this->withdrawalData($w));
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0']]);

        return $this->guarded(fn () => $this->ok($this->withdrawals->preview($this->cafe($request), (float) $data['amount'])));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);

        return $this->guarded(function () use ($request, $data) {
            $withdrawal = $this->withdrawals->request(
                $this->cafe($request), (float) $data['amount'], $data['idempotency_key'], $request->user()
            );

            return $this->ok($this->withdrawalData($withdrawal), $withdrawal->wasRecentlyCreated ? 201 : 200);
        });
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $withdrawal = WithdrawalRequest::where('wallet_id', $this->wallet->walletFor($this->cafe($request))->id)
            ->findOrFail($id);

        return $this->guarded(fn () => $this->ok($this->withdrawalData(
            $this->withdrawals->cancelByOwner($withdrawal, $request->user())
        )));
    }

    public function bankShow(Request $request): JsonResponse
    {
        $bank = BankAccount::where('cafe_id', $this->cafe($request)->id)->first();

        return $this->ok($bank ? $this->bankData($bank) : null);
    }

    public function bankUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        return $this->ok($this->bankData($this->banks->save($this->cafe($request), $data)));
    }

    public function bankDestroy(Request $request): JsonResponse
    {
        BankAccount::where('cafe_id', $this->cafe($request)->id)->delete(); // open requests keep their snapshot

        return $this->ok(null);
    }

    private function cafe(Request $request): Cafe
    {
        return $request->attributes->get('wallet_cafe');
    }

    private function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->input('per_page', 20)));
    }

    private function withdrawalData(WithdrawalRequest $w): array
    {
        $bank = $w->bank_snapshot;

        return [
            'id' => $w->id,
            'reference' => $w->reference,
            'amount' => (float) $w->amount,
            'fee' => (float) $w->fee,
            'net_amount' => (float) $w->net_amount,
            'currency' => $w->currency,
            'status' => $w->status,
            'rejection_reason' => $w->rejection_reason,
            'failure_reason' => $w->failure_reason,
            'bank_account' => ['bank_name' => $bank['bank_name'] ?? null, 'iban_masked' => $bank['iban_masked'] ?? null],
            'created_at' => $w->created_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'reviewed_at' => $w->reviewed_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'paid_at' => $w->paid_at?->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    private function bankData(BankAccount $b): array
    {
        return [
            'id' => $b->id,
            'account_holder_name' => $b->account_holder_name,
            'bank_name' => $b->bank_name,
            'iban_masked' => $b->iban_masked,
            'city' => $b->city,
            'mobile' => $b->mobile,
            'status' => $b->status,
            'rejection_reason' => $b->rejection_reason,
        ];
    }

    private function ok($data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => 'OK'], $status);
    }

    private function paginated($page, callable $map): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map($map)->values(),
            'message' => 'OK',
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    private function guarded(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (WalletException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['code' => [$e->errorCode]],
            ], $e->status);
        }
    }
}
