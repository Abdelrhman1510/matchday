<?php

namespace App\Services\Wallet;

use App\Models\Cafe;
use App\Models\CommissionOverride;
use App\Models\SettingsAuditLog;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** The Fees & Commissions settings, and the single place fees are calculated. */
class FeeSettingsService
{
    public const DEFAULTS = [
        'payments' => ['online_enabled' => false],
        'booking' => ['payment_hold_minutes' => 10],
        'booking_fee' => ['enabled' => true, 'type' => 'fixed', 'value' => 5.0, 'min' => 0.0, 'max' => 0.0],
        'commission' => ['enabled' => false, 'type' => 'percent', 'value' => 0.0, 'min' => 0.0, 'max' => 0.0],
        'vat' => ['enabled' => false, 'rate' => 15.0],
        'gateway_fee' => ['bearer' => 'platform'],
        'withdrawals' => [
            'enabled' => true, 'min_amount' => 100.0, 'max_amount' => 0.0, 'fee' => 0.0,
            'hold_hours' => 0, 'one_open_request' => true, 'payout_mode' => 'moyasar',
        ],
        'subscriptions' => ['enabled' => true],
    ];

    public function __construct(private PlatformSettingsService $settings) {}

    public function all(): array
    {
        $blocks = [];
        foreach (array_keys(self::DEFAULTS) as $name) {
            $blocks[$name] = $this->block($name);
        }

        return $blocks;
    }

    public function block(string $name): array
    {
        $stored = $this->settings->get("fees.{$name}");

        return array_merge(self::DEFAULTS[$name], is_array($stored) ? $stored : []);
    }

    public function onlineEnabled(): bool
    {
        return (bool) $this->block('payments')['online_enabled'];
    }

    public function subscriptionsEnabled(): bool
    {
        return (bool) $this->block('subscriptions')['enabled'];
    }

    /** Partial update: unknown blocks and fields are ignored; every change is audited. */
    public function update(array $input, ?User $user): array
    {
        $current = $this->all();
        $next = $current;
        foreach ($input as $block => $fields) {
            if (!isset(self::DEFAULTS[$block]) || !is_array($fields)) {
                continue;
            }
            $next[$block] = array_merge($current[$block], array_intersect_key($fields, self::DEFAULTS[$block]));
        }

        $this->validate($next);
        $next = $this->normalize($next);

        DB::transaction(function () use ($current, $next, $user) {
            foreach ($next as $block => $values) {
                if ($values == $current[$block]) {
                    continue;
                }
                $this->settings->set("fees.{$block}", $values, 'json', 'fees');
                SettingsAuditLog::create([
                    'key' => $block, 'old' => $current[$block], 'new' => $values, 'user_id' => $user?->id,
                ]);
            }
        });

        return $next;
    }

    private function validate(array $s): void
    {
        $money = ['required', 'numeric', 'min:0'];
        $validator = Validator::make($s, [
            'payments.online_enabled' => ['required', 'boolean'],
            'booking.payment_hold_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'booking_fee.enabled' => ['required', 'boolean'],
            'booking_fee.type' => ['required', 'in:fixed,percent,per_guest'],
            'booking_fee.value' => $money,
            'booking_fee.min' => $money,
            'booking_fee.max' => $money,
            'commission.enabled' => ['required', 'boolean'],
            'commission.type' => ['required', 'in:percent,fixed_per_booking,fixed_per_guest'],
            'commission.value' => $money,
            'commission.min' => $money,
            'commission.max' => $money,
            'vat.enabled' => ['required', 'boolean'],
            'vat.rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'gateway_fee.bearer' => ['required', 'in:platform,cafe'],
            'withdrawals.enabled' => ['required', 'boolean'],
            'withdrawals.min_amount' => $money,
            'withdrawals.max_amount' => $money,
            'withdrawals.fee' => $money,
            'withdrawals.hold_hours' => ['required', 'integer', 'min:0', 'max:8760'],
            'withdrawals.one_open_request' => ['required', 'boolean'],
            'withdrawals.payout_mode' => ['required', 'in:moyasar,manual'],
            'subscriptions.enabled' => ['required', 'boolean'],
        ]);

        $validator->after(function ($v) use ($s) {
            foreach (['booking_fee', 'commission'] as $block) {
                if ($s[$block]['type'] === 'percent' && (float) $s[$block]['value'] > 100) {
                    $v->errors()->add("{$block}.value", __('wallet.settings.percent_range'));
                }
                if ((float) $s[$block]['min'] > 0 && (float) $s[$block]['max'] > 0
                    && (float) $s[$block]['max'] < (float) $s[$block]['min']) {
                    $v->errors()->add("{$block}.max", __('wallet.settings.max_gte_min'));
                }
            }
            $w = $s['withdrawals'];
            if ((float) $w['max_amount'] > 0 && (float) $w['max_amount'] < (float) $w['min_amount']) {
                $v->errors()->add('withdrawals.max_amount', __('wallet.settings.max_gte_min'));
            }
            if ((float) $w['min_amount'] <= (float) $w['fee']) {
                $v->errors()->add('withdrawals.min_amount', __('wallet.settings.min_gt_fee'));
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function normalize(array $s): array
    {
        foreach ($s as $block => $fields) {
            foreach ($fields as $key => $value) {
                $default = self::DEFAULTS[$block][$key];
                $s[$block][$key] = match (true) {
                    is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    is_int($default) => (int) $value,
                    is_float($default) => round((float) $value, 2),
                    default => $value,
                };
            }
        }

        return $s;
    }

    public function publicConfig(): array
    {
        $fee = $this->block('booking_fee');

        return [
            'currency' => 'SAR',
            'online_payments_enabled' => $this->onlineEnabled(),
            'booking_fee' => [
                'enabled' => (bool) $fee['enabled'],
                'type' => $fee['type'],
                'value' => (float) $fee['value'],
                'min' => (float) $fee['min'],
                'max' => (float) $fee['max'],
            ],
        ];
    }

    public function serviceFee(float $subtotal, int $guests, ?array $rule = null): float
    {
        $rule ??= $this->block('booking_fee');
        if (empty($rule['enabled'])) {
            return 0.0;
        }

        $fee = match ($rule['type']) {
            'per_guest' => (float) $rule['value'] * $guests,
            'percent' => $this->clamp($subtotal * (float) $rule['value'] / 100, (float) $rule['min'], (float) $rule['max']),
            default => (float) $rule['value'],
        };

        return round($fee, 2);
    }

    /** Precedence: cafe override > plan override > default. */
    public function commissionRule(Cafe $cafe): array
    {
        $default = $this->block('commission');
        if (empty($default['enabled'])) {
            return $default;
        }

        $override = CommissionOverride::where('scope', 'cafe')->where('scope_id', $cafe->id)->first();
        if (!$override) {
            $planId = $cafe->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->latest('expires_at')
                ->value('plan_id');
            $override = $planId
                ? CommissionOverride::where('scope', 'plan')->where('scope_id', $planId)->first()
                : null;
        }
        if (!$override) {
            return $default;
        }

        return [
            'enabled' => true,
            'type' => $override->type,
            'value' => (float) $override->value,
            'min' => (float) $override->min,
            'max' => (float) $override->max,
            'override' => $override->scope,
        ];
    }

    public function commission(float $subtotal, int $guests, array $rule): float
    {
        if (empty($rule['enabled'])) {
            return 0.0;
        }

        $amount = match ($rule['type']) {
            'fixed_per_booking' => (float) $rule['value'],
            'fixed_per_guest' => (float) $rule['value'] * $guests,
            default => $this->clamp($subtotal * (float) $rule['value'] / 100, (float) $rule['min'], (float) $rule['max']),
        };

        return round(min($amount, $subtotal), 2); // a cafe never owes more than the booking earned
    }

    /** The money snapshot stored on a booking when it is created. */
    public function quote(Cafe $cafe, float $subtotal, int $guests, bool $online): array
    {
        $feeRule = $this->block('booking_fee');
        $commissionRule = $online ? $this->commissionRule($cafe) : ['enabled' => false];
        $serviceFee = $this->serviceFee($subtotal, $guests, $feeRule);
        $commission = $online ? $this->commission($subtotal, $guests, $commissionRule) : 0.0;
        $subtotal = round($subtotal, 2);

        return [
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'total_amount' => round($subtotal + $serviceFee, 2),
            'commission_amount' => $commission,
            'gateway_fee' => 0.0, // known only once Moyasar reports it, see BookingPaymentService
            'cafe_net_amount' => round($subtotal - $commission, 2),
            'fees_snapshot' => [
                'booking_fee' => $feeRule,
                'commission' => $commissionRule,
                'gateway_fee' => $this->block('gateway_fee'),
                'vat' => $this->block('vat'),
            ],
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        if ($min > 0) {
            $value = max($value, $min);
        }

        return $max > 0 ? min($value, $max) : $value;
    }
}
