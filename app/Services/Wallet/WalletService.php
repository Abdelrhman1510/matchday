<?php

namespace App\Services\Wallet;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** The ledger. Balances are always sums over wallet_transactions, never stored. */
class WalletService
{
    public const EARNING_TYPES = ['booking_earning', 'commission', 'gateway_fee'];
    public const OPEN_WITHDRAWALS = ['pending', 'approved', 'processing'];

    public function __construct(private FeeSettingsService $fees) {}

    public function walletFor(Cafe $cafe): Wallet
    {
        return Wallet::firstOrCreate(['cafe_id' => $cafe->id], ['currency' => 'SAR']);
    }

    public function lock(Wallet $wallet): Wallet
    {
        return Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
    }

    public function balances(Wallet $wallet): array
    {
        $rows = WalletTransaction::where('wallet_id', $wallet->id)
            ->select('type', 'direction', 'status', DB::raw('SUM(amount) as total'))
            ->groupBy('type', 'direction', 'status')
            ->get();

        $available = $pending = $earned = 0.0;
        foreach ($rows as $row) {
            $signed = ($row->direction === 'credit' ? 1 : -1) * (float) $row->total;
            if ($row->status === 'available') {
                $available += $signed;
            } elseif ($row->status === 'pending') {
                $pending += $signed;
            }
            if ($row->status !== 'reversed' && (in_array($row->type, self::EARNING_TYPES, true) || $row->type === 'refund')) {
                $earned += $signed;
            }
        }

        $withdrawals = WithdrawalRequest::where('wallet_id', $wallet->id)
            ->select('status', DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => round($available + $pending, 2),
            'available' => round($available, 2),
            'pending' => round($pending, 2),
            'pending_withdrawal' => round(collect(self::OPEN_WITHDRAWALS)->sum(fn ($s) => (float) ($withdrawals[$s] ?? 0)), 2),
            'total_withdrawn' => round((float) ($withdrawals['paid'] ?? 0), 2),
            'total_earned' => round($earned, 2),
        ];
    }

    public function kickOff(GameMatch $match): Carbon
    {
        // Match times are entered and stored in Riyadh time.
        return Carbon::parse($match->match_date->format('Y-m-d') . ' ' . $match->kick_off->format('H:i:s'), 'Asia/Riyadh')
            ->setTimezone(config('app.timezone'));
    }

    /** Writes the earning and its deductions once; a replayed confirmation is a no-op. */
    public function recordBookingEarning(Booking $booking): void
    {
        $booking->loadMissing('branch', 'match.homeTeam', 'match.awayTeam');
        $cafe = Cafe::withTrashed()->find($booking->branch?->cafe_id);
        if (!$cafe) {
            return;
        }

        $wallet = $this->lock($this->walletFor($cafe));
        if (WalletTransaction::where('booking_id', $booking->id)->where('type', 'booking_earning')->exists()) {
            return;
        }

        $hold = (int) $this->fees->block('withdrawals')['hold_hours'];
        $releaseFrom = $this->kickOff($booking->match);
        if ($booking->checked_in_at && $booking->checked_in_at->lt($releaseFrom)) {
            $releaseFrom = $booking->checked_in_at->copy();
        }
        $availableAt = $releaseFrom->addHours($hold);
        $status = $availableAt->lte(now()) ? 'available' : 'pending';

        $code = $booking->booking_code;
        $description = "{$booking->match->homeTeam->name} vs {$booking->match->awayTeam->name} · {$booking->guests_count} guests";
        $descriptionAr = ($booking->match->homeTeam->name_ar ?? $booking->match->homeTeam->name) . ' ضد '
            . ($booking->match->awayTeam->name_ar ?? $booking->match->awayTeam->name) . " · {$booking->guests_count} ضيوف";

        $write = fn (string $type, string $direction, float $amount, string $title, string $titleAr) => WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => $type, 'direction' => $direction, 'amount' => $amount,
            'status' => $status, 'booking_id' => $booking->id, 'title' => $title, 'title_ar' => $titleAr,
            'description' => $description, 'description_ar' => $descriptionAr, 'reference' => $code,
            'available_at' => $availableAt,
        ]);

        $write('booking_earning', 'credit', (float) $booking->subtotal, "Booking {$code}", "حجز {$code}");

        if ((float) $booking->commission_amount > 0) {
            $rule = $booking->fees_snapshot['commission'] ?? [];
            $rate = ($rule['type'] ?? null) === 'percent' ? ' (' . rtrim(rtrim(number_format((float) $rule['value'], 2), '0'), '.') . '%)' : '';
            $write('commission', 'debit', (float) $booking->commission_amount, "Shaj3 commission{$rate}", "عمولة Shaj3{$rate}");
        }
        if ((float) $booking->gateway_fee > 0) {
            $write('gateway_fee', 'debit', (float) $booking->gateway_fee, 'Card processing fee', 'رسوم معالجة البطاقة');
        }
    }

    /** Check-in releases the earning now (hold 0) or moves its release earlier. */
    public function onCheckIn(Booking $booking): void
    {
        $first = $this->earningQuery($booking)->where('status', 'pending')->first();
        if (!$first) {
            return;
        }

        $hold = (int) $this->fees->block('withdrawals')['hold_hours'];
        $candidate = ($booking->checked_in_at ?? now())->copy()->addHours($hold);
        $availableAt = $first->available_at && $first->available_at->lt($candidate) ? $first->available_at : $candidate;

        $this->earningQuery($booking)->where('status', 'pending')->update([
            'available_at' => $availableAt,
            'status' => $availableAt->lte(now()) ? 'available' : 'pending',
            'updated_at' => now(),
        ]);
    }

    public function promoteDue(): int
    {
        // A booking's rows share one available_at, so they always move together.
        return WalletTransaction::where('status', 'pending')
            ->whereIn('type', self::EARNING_TYPES)
            ->where('available_at', '<=', now())
            ->update(['status' => 'available', 'updated_at' => now()]);
    }

    /** A postponed match moves the release of earnings that are still on hold. */
    public function rescheduleForMatch(GameMatch $match): void
    {
        $hold = (int) $this->fees->block('withdrawals')['hold_hours'];
        $availableAt = $this->kickOff($match)->addHours($hold);

        $bookingIds = Booking::where('match_id', $match->id)->whereNull('checked_in_at')->pluck('id');
        foreach ($bookingIds->chunk(500) as $chunk) {
            WalletTransaction::whereIn('booking_id', $chunk->all())
                ->whereIn('type', self::EARNING_TYPES)
                ->where('status', 'pending')
                ->update(['available_at' => $availableAt, 'updated_at' => now()]);
        }
    }

    /** On hold: void the entries. Already released: write one refund debit. */
    public function reverseBooking(Booking $booking): void
    {
        $earning = $this->earningQuery($booking)->where('type', 'booking_earning')->first();
        if (!$earning || $earning->status === 'reversed') {
            return;
        }

        $wallet = $this->lock($earning->wallet);

        if ($earning->status === 'pending') {
            $this->earningQuery($booking)->update(['status' => 'reversed', 'updated_at' => now()]);
            return;
        }

        if (WalletTransaction::where('booking_id', $booking->id)->where('type', 'refund')->exists()) {
            return;
        }

        $deductions = (float) $this->earningQuery($booking)->where('direction', 'debit')->sum('amount');
        WalletTransaction::create([
            'wallet_id' => $wallet->id, 'type' => 'refund', 'direction' => 'debit',
            'amount' => round((float) $earning->amount - $deductions, 2), 'status' => 'available',
            'booking_id' => $booking->id, 'title' => "Refund {$booking->booking_code}",
            'title_ar' => "استرداد {$booking->booking_code}", 'description' => $earning->description,
            'description_ar' => $earning->description_ar, 'reference' => $booking->booking_code,
            'available_at' => now(),
        ]);
    }

    public function adjustment(Wallet $wallet, string $direction, float $amount, string $reason, User $by): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $direction, $amount, $reason, $by) {
            $wallet = $this->lock($wallet);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id, 'type' => 'adjustment', 'direction' => $direction,
                'amount' => round($amount, 2), 'status' => 'available', 'title' => 'Balance adjustment',
                'title_ar' => 'تعديل الرصيد', 'description' => $reason, 'description_ar' => $reason,
                'available_at' => now(), 'created_by' => $by->id,
            ]);
        });
    }

    private function earningQuery(Booking $booking)
    {
        return WalletTransaction::where('booking_id', $booking->id)->whereIn('type', self::EARNING_TYPES);
    }
}
