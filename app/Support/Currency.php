<?php

namespace App\Support;

/**
 * Currency code → localized display name.
 *
 * Used to expose an Arabic currency label (`currency_ar`) alongside the raw
 * ISO code in API responses, so clients can render either language without
 * hardcoding the mapping themselves.
 */
class Currency
{
    /** ISO 4217 code → Arabic name. */
    public const NAMES_AR = [
        'SAR' => 'ريال سعودي',
        'AED' => 'درهم إماراتي',
        'USD' => 'دولار أمريكي',
        'EGP' => 'جنيه مصري',
        'KWD' => 'دينار كويتي',
        'QAR' => 'ريال قطري',
        'BHD' => 'دينار بحريني',
        'OMR' => 'ريال عماني',
    ];

    /**
     * Arabic display name for a currency code; falls back to the code itself
     * when there is no known translation.
     */
    public static function arabicName(?string $code): string
    {
        $code = strtoupper((string) $code);

        return self::NAMES_AR[$code] ?? $code;
    }
}
