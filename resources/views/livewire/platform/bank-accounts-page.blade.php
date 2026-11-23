@php
    $input = 'bg-[#160d45] border border-[#1e164e] rounded-lg px-3 py-2 text-white text-sm focus:border-[#c8ff00] focus:outline-none';
    $canApprove = auth()->user()->can('withdrawals.approve');
@endphp
<div class="space-y-6 pb-24">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <h1 class="text-3xl font-black font-bungee text-white uppercase tracking-wider">{{ __('finance.bank_accounts_title') }}</h1>
        <select wire:model.live="status" class="{{ $input }}">
            <option value="pending_verification">{{ __('finance.bank_status.pending_verification') }}</option>
            <option value="verified">{{ __('finance.bank_status.verified') }}</option>
            <option value="rejected">{{ __('finance.bank_status.rejected') }}</option>
            <option value="">{{ __('finance.all') }}</option>
        </select>
    </div>

    @if (session()->has('message'))<div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('message') }}</div>@endif

    <p class="text-sm text-slate-400">{{ __('finance.verify_rule') }}</p>

    <div class="space-y-4">
        @forelse ($accounts as $a)
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5 space-y-3" wire:key="bank-{{ $a->id }}">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm text-slate-300">
                    <div><p class="text-xs text-slate-500">{{ __('finance.cafe') }}</p>{{ $a->cafe?->name }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.owner') }}</p>{{ $a->cafe?->owner?->name }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.holder') }}</p><b class="text-white">{{ $a->account_holder_name }}</b></div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.bank') }}</p>{{ $a->bank_name }}</div>
                    <div class="col-span-2"><p class="text-xs text-slate-500">IBAN</p>
                        <span dir="ltr" class="font-mono text-white">{{ $revealed[$a->id] ?? $a->iban_masked }}</span>
                        @can('finance.reveal_iban')
                            @unless (isset($revealed[$a->id]))<button wire:click="reveal({{ $a->id }})" class="ms-2 text-xs text-[#c8ff00] underline">{{ __('finance.reveal') }}</button>@endunless
                        @endcan
                    </div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.city') }}</p>{{ $a->city }}</div>
                    <div><p class="text-xs text-slate-500">{{ __('finance.status_label') }}</p>{{ __('finance.bank_status.' . $a->status) }}</div>
                </div>
                @if ($a->rejection_reason)<p class="text-sm text-red-300">{{ $a->rejection_reason }}</p>@endif
                @if ($canApprove && $a->status === 'pending_verification')
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button wire:click="verify({{ $a->id }})" wire:confirm="{{ __('finance.verify_confirm') }}" class="px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold text-sm rounded-lg">{{ __('finance.verify') }}</button>
                        <input wire:model="reasons.{{ $a->id }}" placeholder="{{ __('finance.reject_reason') }}" class="{{ $input }} flex-1">
                        <button wire:click="reject({{ $a->id }})" class="px-4 py-2 border border-red-700 hover:bg-red-900/30 text-red-300 text-sm rounded-lg">{{ __('finance.reject') }}</button>
                    </div>
                    @error("reasons.{$a->id}")<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                @endif
            </div>
        @empty
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-10 text-center text-slate-500">{{ __('finance.none') }}</div>
        @endforelse
    </div>
    {{ $accounts->links() }}
</div>
