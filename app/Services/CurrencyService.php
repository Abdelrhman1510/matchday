<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /** Exchange rates are cached for 6 hours */
    private const RATE_TTL = 21600;

    /** IP→country lookups are cached for 24 hours */
    private const GEO_TTL = 86400;

    /** Currencies the platform supports */
    private const SUPPORTED = ['SAR', 'USD', 'EUR', 'GBP', 'AED'];

    private PlatformSettingsService $settings;

    public function __construct(PlatformSettingsService $settings)
    {
        $this->settings = $settings;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Convert an amount from one currency to another.
     * Returns the original amount if conversion fails or isn't needed.
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if ($from === $to || $amount === 0.0) {
            return $amount;
        }

        $rate = $this->getExchangeRate($from, $to);

        return round($amount * $rate, 2);
    }

    /**
     * Determine the effective currency for this request.
     *
     * Priority:
     *   1. X-Currency request header (mobile app sends preferred currency)
     *   2. IP-based geolocation (only when auto_conversion is ON)
     *   3. Platform default currency
     */
    public function getUserCurrency(Request $request): string
    {
        $default = $this->settings->get('default_currency', 'SAR');

        // 1. Explicit header from mobile app
        $header = strtoupper((string) $request->header('X-Currency', ''));
        if ($header && $this->isSupported($header)) {
            return $header;
        }

        // 2. IP-based detection (only when the admin has enabled both toggles)
        if (
            $this->settings->get('auto_conversion', false) &&
            $this->settings->get('multi_currency', false)
        ) {
            $ip = $request->ip();
            if ($ip && !in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
                $detected = $this->detectCurrencyFromIp($ip);
                if ($detected) {
                    return $detected;
                }
            }
        }

        return $default;
    }

    /**
     * Get the exchange rate between two currencies.
     * Uses frankfurter.app (free, no API key needed).
     * Returns 1.0 on failure so amounts pass through unchanged.
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        return Cache::remember(
            "exchange_rate_{$from}_{$to}",
            self::RATE_TTL,
            function () use ($from, $to): float {
                try {
                    $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                        'from' => $from,
                        'to'   => $to,
                    ]);

                    if ($response->successful()) {
                        return (float) ($response->json("rates.{$to}") ?? 1.0);
                    }
                } catch (\Throwable $e) {
                    Log::warning("CurrencyService: failed to fetch {$from}→{$to} rate", [
                        'error' => $e->getMessage(),
                    ]);
                }

                return 1.0;
            }
        );
    }

    /**
     * Get all rates from the base currency in one request (for bulk conversion).
     * Returns ['USD' => 0.266, 'EUR' => 0.245, ...]
     */
    public function getRatesFrom(string $base): array
    {
        $base = strtoupper($base);

        return Cache::remember(
            "exchange_rates_from_{$base}",
            self::RATE_TTL,
            function () use ($base): array {
                try {
                    $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                        'from' => $base,
                        'to'   => implode(',', array_filter(self::SUPPORTED, fn ($c) => $c !== $base)),
                    ]);

                    if ($response->successful()) {
                        return $response->json('rates') ?? [];
                    }
                } catch (\Throwable $e) {
                    Log::warning("CurrencyService: failed to fetch rates from {$base}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                return [];
            }
        );
    }

    /**
     * Get supported currency list with their current rates relative to the
     * platform default currency. Useful for the mobile "currency picker".
     */
    public function getSupportedCurrencies(): array
    {
        $base  = strtoupper($this->settings->get('default_currency', 'SAR'));
        $rates = $this->getRatesFrom($base);

        $result = [];
        foreach (self::SUPPORTED as $code) {
            $result[] = [
                'code'   => $code,
                'rate'   => $code === $base ? 1.0 : (float) ($rates[$code] ?? 1.0),
                'symbol' => $this->symbol($code),
            ];
        }

        return $result;
    }

    /**
     * Whether auto-conversion is currently active.
     */
    public function isAutoConversionEnabled(): bool
    {
        return (bool) $this->settings->get('auto_conversion', false)
            && (bool) $this->settings->get('multi_currency', false);
    }

    /**
     * Return the symbol for a currency code.
     */
    public function symbol(string $code): string
    {
        return match (strtoupper($code)) {
            'SAR'  => 'ر.س',
            'USD'  => '$',
            'EUR'  => '€',
            'GBP'  => '£',
            'AED'  => 'د.إ',
            default => strtoupper($code),
        };
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function detectCurrencyFromIp(string $ip): ?string
    {
        return Cache::remember(
            "ip_currency_{$ip}",
            self::GEO_TTL,
            function () use ($ip): ?string {
                try {
                    // ip-api.com — free tier, no key, 45 req/min
                    $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                        'fields' => 'countryCode,status',
                    ]);

                    if ($response->successful() && $response->json('status') === 'success') {
                        $country = (string) $response->json('countryCode');
                        return $this->countryToCurrency($country);
                    }
                } catch (\Throwable $e) {
                    Log::debug("CurrencyService: IP geo failed for {$ip}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                return null;
            }
        );
    }

    private function countryToCurrency(string $countryCode): string
    {
        $default = $this->settings->get('default_currency', 'SAR');

        $map = [
            // Gulf
            'SA' => 'SAR', 'BH' => 'SAR', 'QA' => 'SAR', 'KW' => 'SAR',
            'AE' => 'AED',
            // UK
            'GB' => 'GBP',
            // USD zone
            'US' => 'USD', 'CA' => 'USD', 'AU' => 'USD',
            // Eurozone
            'FR' => 'EUR', 'DE' => 'EUR', 'ES' => 'EUR', 'IT' => 'EUR',
            'NL' => 'EUR', 'PT' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR',
            'IE' => 'EUR', 'FI' => 'EUR', 'GR' => 'EUR', 'CY' => 'EUR',
            'EE' => 'EUR', 'LV' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR',
            'MT' => 'EUR', 'SK' => 'EUR', 'SI' => 'EUR',
        ];

        $currency = $map[$countryCode] ?? $default;

        // Only return supported currencies
        return $this->isSupported($currency) ? $currency : $default;
    }

    private function isSupported(string $currency): bool
    {
        return in_array(strtoupper($currency), self::SUPPORTED, true);
    }
}
