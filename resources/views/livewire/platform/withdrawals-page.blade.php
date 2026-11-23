@php
    $card = 'bg-[#0e0735] border border-[#1e164e] rounded-xl p-5';
    $input = 'w-full bg-[#160d45] border border-[#1e164e] rounded-lg px-3 py-2 text-white text-sm focus:border-[#c8ff00] focus:outline-none';
    $primary = 'px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold text-sm rounded-lg';
    $secondary = 'px-4 py-2 border border-[#1e164e] hover:border-slate-500 text-white text-sm rounded-lg';
    $danger = 'px-4 py-2 border border-red-700 hover:bg-red-900/30 text-red-300 text-sm rounded-lg';
    $badge = fn ($s) => match ($s) {
        'paid' => 'bg-green-600/20 text-green-400',
        'pending' => 'bg-amber-600/20 text-amber-300',
        'approved', 'processing' => 'bg-sky-600/20 text-sky-300',
        'failed', 'rejected' => 'bg-red-600/20 text-red-300',
        default => 'bg-slate-600/20 text-slate-300',
    };
    $canApprove = auth()->user()->can('withdrawals.approve');
@endphp
<div class="space-y-6 pb-24">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-black font-bungee text-white uppercase tracking-wider">{{ __('finance.withdrawals_title') }}</h1>
        <button wire:click="exportCsv" class="{{ $secondary }}">{{ __('finance.export_csv') }}</button>
    </div>

    @if (session()->has('message'))<div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('message') }}</div>@endif
    @if (session()->has('error'))<div class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-3 rounded-lg text-sm">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="{{ $card }}"><p class="text-xs text-slate-400">{{ __('finance.pending_count') }}</p><p class="text-2xl font-bold text-white">{{ $totals['pending_count'] }}</p></div>
        <div class="{{ $card }}"><p class="text-xs text-slate-400">{{ __('finance.pending_amount') }}</p><p class="text-2xl font-bold text-white">{{ number_format($totals['pending_amount'], 2) }} SAR</p></div>
        <div class="{{ $card }}"><p class="text-xs text-slate-400">{{ __('finance.paid_month') }}</p><p class="text-2xl font-bold text-white">{{ number_format($totals['paid_month'], 2) }} SAR</p></div>
    </div>

    <div class="{{ $card }} grid grid-cols-2 lg:grid-cols-6 gap-3">
        <select wire:model.live="status" class="{{ $input }}"><option value="">{{ __('finance.all') }}</option>@foreach (['pending','approved','processing','paid','rejected','failed','cancelled'] as $s)<option value="{{ $s }}">{{ __('finance.status.' . $s) }}</option>@endforeach</select>
        <input type="search" wire:model.live.debounce.300ms="cafe" placeholder="{{ __('finance.cafe') }}" class="{{ $input }}">
        <input type="date" wire:model.live="dateFrom" class="{{ $input }}">
        <input type="date" wire:model.live="dateTo" class="{{ $input }}">
        <input type="number" min="0" wire:model.live.debounce.300ms="amountMin" placeholder="{{ __('finance.min') }}" class="{{ $input }}">
        <input type="number" min="0" wire:model.live.debounce.300ms="amountMax" placeholder="{{ __('finance.max') }}" class="{{ $input }}">
    </div>

    <div class="{{ $card }} overflow-x-auto">
        <table class="w-full text-sm text-slate-300 whitespace-nowrap">
            <thead class="text-xs text-slate-400"><tr>@foreach (['reference','cafe','owner','amount','fee','net','bank','status_label','requested_at','reviewed_by'] as $c)<th class="text-start p-2">{{ __('finance.' . $c) }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($requests as $w)
                    <tr class="border-t border-[#1e164e] hover:bg-[#160d45] cursor-pointer" wire:click="select({{ $w->id }})" wire:key="w-{{ $w->id }}">
                        <td class="p-2 font-mono text-white">{{ $w->reference }}</td>
                        <td class="p-2">{{ $w->wallet->cafe?->name }}</td>
                        <td class="p-2">{{ $w->wallet->cafe?->owner?->name }}<div class="text-xs text-slate-500">{{ $w->wallet->cafe?->owner?->phone }}</div></td>
                        <td class="p-2">{{ number_format($w->amount, 2) }}</td>
                        <td class="p-2">{{ number_format($w->fee, 2) }}</td>
                        <td class="p-2 font-bold text-white">{{ number_format($w->net_amount, 2) }}</td>
                        <td class="p-2">{{ $w->bank_snapshot['bank_name'] ?? '' }}<div class="text-xs text-slate-500" dir="ltr">{{ $w->bank_snapshot['iban_masked'] ?? '' }}</div></td>
                        <td class="p-2"><span class="px-2 py-1 rounded text-xs {{ $badge($w->status) }}">{{ __('finance.status.' . $w->status) }}</span></td>
                        <td class="p-2">{{ $w->created_at->format('Y-m-d H:i') }}</td>
                        <td class="p-2">{{ $w->reviewer?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="p-6 text-center text-slate-500">{{ __('finance.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $requests->links() }}</div>
    </div>

    @if ($selected)
        <div class="fixed inset-0 z-50 flex justify-end bg-black/60">
            <div class="w-full max-w-2xl h-full overflow-y-auto bg-[#0a0526] border-s border-[#1e164e] p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <div><h2 class="text-xl font-bold text-white font-mono">{{ $selected->reference }}</h2><span class="px-2 py-1 rounded text-xs {{ $badge($selected->status) }}">{{ __('finance.status.' . $selected->status) }}</span></div>
                    <button wire:click="closeDetail" class="{{ $secondary }}">{{ __('finance.close') }}</button>
                </div>

                <div class="{{ $card }} grid grid-cols-2 gap-3 text-sm text-slate-300">
                    <div><p class="text-xs text-slate-500">{{ __('finance.cafe') }}</p>{{ $selected->wallet->cafe?->name }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.owner') }}</p>{{ $selected->wallet->cafe?->owner?->name }} · {{ $selected->wallet->cafe?->owner?->phone }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.holder') }}</p>{{ $selected->bank_snapshot['account_holder_name'] ?? '' }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.bank') }}</p>{{ $selected->bank_snapshot['bank_name'] ?? '' }}</div>
                    <div class="col-span-2"><p class="text-xs text-slate-500">IBAN</p>
                        <span dir="ltr" class="font-mono text-white">{{ $revealedIban ?? ($selected->bank_snapshot['iban_masked'] ?? '') }}</span>
                        @can('finance.reveal_iban')
                            @unless ($revealedIban)<button wire:click="revealIban" class="ms-2 text-xs text-[#c8ff00] underline">{{ __('finance.reveal') }}</button>@endunless
                        @endcan
                    </div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.net') }}</p><b class="text-white">{{ number_format($selected->net_amount, 2) }} SAR</b></div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.available_then_now') }}</p>{{ number_format($balanceAtRequest, 2) }} / {{ number_format($balances['available'], 2) }}</div>
                </div>

                @if ($selected->moyasar_payout_id || $selected->payout_uuid)
                    <div class="{{ $card }} text-sm text-slate-300 space-y-1">
                        <h3 class="font-bold text-white">{{ __('finance.moyasar_payout') }}</h3>
                        <div class="font-mono text-xs break-all">{{ $selected->moyasar_payout_id ?? $selected->payout_uuid }}</div>
                        <div>{{ __('finance.status_label') }}: {{ $selected->moyasar_status ?? '—' }} · {{ __('finance.channel') }}: {{ $selected->moyasar_channel ?? '—' }}</div>
                        @if ($selected->moyasar_message)<div>{{ $selected->moyasar_message }}</div>@endif
                        @if ($selected->failure_reason)<div class="text-red-300">{{ $selected->failure_reason }}</div>@endif
                        @if ($selected->payout_blocked)<div class="text-red-300 font-bold">{{ __('finance.payout_blocked') }}</div>@endif
                        <div class="text-xs text-slate-500">{{ __('finance.last_checked') }}: {{ $selected->moyasar_checked_at?->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                @endif

                @if ($canApprove)
                    <div class="{{ $card }} space-y-3">
                        <div class="flex flex-wrap gap-2">
                            @if ($selected->status === 'pending')
                                <button wire:click="approve" wire:confirm="{{ __('finance.approve_confirm', ['net' => number_format($selected->net_amount, 2), 'iban' => $selected->bank_snapshot['iban_masked'] ?? '']) }}" class="{{ $primary }}">{{ __('finance.approve_send') }}</button>
                            @endif
                            @if ($selected->status === 'failed')
                                <button wire:click="retry" wire:confirm="{{ __('finance.retry_confirm') }}" class="{{ $primary }}">{{ __('finance.retry') }}</button>
                            @endif
                            @if ($selected->status === 'paid')
                                <button wire:click="markReturned" wire:confirm="{{ __('finance.returned_confirm') }}" class="{{ $danger }}">{{ __('finance.mark_returned') }}</button>
                            @endif
                        </div>
                        @if (in_array($selected->status, ['pending', 'approved'], true))
                            <div class="flex gap-2"><input wire:model="rejectReason" placeholder="{{ __('finance.reject_reason') }}" class="{{ $input }}"><button wire:click="reject" wire:confirm="{{ __('finance.reject_confirm') }}" class="{{ $danger }}">{{ __('finance.reject') }}</button></div>
                        @endif
                        @if (in_array($selected->status, ['pending', 'approved', 'failed'], true))
                            <div class="border-t border-[#1e164e] pt-3 space-y-2">
                                <p class="text-sm font-bold text-white">{{ __('finance.mark_paid') }}</p>
                                <div class="grid grid-cols-2 gap-2"><input wire:model="transferRef" placeholder="{{ __('finance.transfer_ref') }}" class="{{ $input }}"><input type="date" wire:model="transferDate" class="{{ $input }}"></div>
                                <input type="file" wire:model="receipt" accept=".jpg,.jpeg,.png,.pdf" class="text-sm text-slate-400">
                                <button wire:click="markPaid" wire:confirm="{{ __('finance.mark_paid_confirm') }}" class="{{ $secondary }}">{{ __('finance.mark_paid') }}</button>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="{{ $card }} text-sm text-slate-300 space-y-1">
                    <h3 class="font-bold text-white mb-2">{{ __('finance.timeline') }}</h3>
                    @foreach ($selected->events as $event)
                        <div class="border-t border-[#1e164e] py-1"><span class="text-xs text-slate-500">{{ $event->created_at?->format('Y-m-d H:i') }}</span> · {{ $event->action }} @if($event->to_status) → {{ $event->to_status }}@endif · {{ $event->user?->name ?? 'system' }}@if($event->note)<div class="text-xs text-slate-400">{{ $event->note }}</div>@endif</div>
                    @endforeach
                </div>

                <div class="{{ $card }} text-sm text-slate-300">
                    <h3 class="font-bold text-white mb-2">{{ __('finance.ledger') }}</h3>
                    @foreach ($ledger as $t)
                        <div class="flex justify-between border-t border-[#1e164e] py-1"><span>{{ $t->title }} <span class="text-xs text-slate-500">({{ $t->status }})</span></span><span class="{{ $t->direction === 'credit' ? 'text-green-400' : 'text-red-300' }}" dir="ltr">{{ $t->direction === 'credit' ? '+' : '−' }}{{ number_format($t->amount, 2) }}</span></div>
                    @endforeach
                </div>

                @if ($previous->isNotEmpty())
                    <div class="{{ $card }} text-sm text-slate-300">
                        <h3 class="font-bold text-white mb-2">{{ __('finance.previous') }}</h3>
                        @foreach ($previous as $p)
                            <div class="flex justify-between border-t border-[#1e164e] py-1"><span class="font-mono">{{ $p->reference }}</span><span>{{ number_format($p->amount, 2) }} · {{ __('finance.status.' . $p->status) }}</span></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
