<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\PlatformSettingsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PlatformSettingsService $settings,
        private CurrencyService $currency,
    ) {}

    /**
     * GET /api/v1/app/version
     */
    public function version(): JsonResponse
    {
        return $this->successResponse([
            'current_version' => '1.0.0',
            'min_version'     => '1.0.0',
            'force_update'    => false,
        ], 'App version retrieved successfully.');
    }

    /**
     * GET /api/v1/app/config
     */
    public function config(Request $request): JsonResponse
    {
        return $this->successResponse([
            'maintenance_mode'    => false,
            'support_email'       => 'support@matchday.app',
            'support_phone'       => '+966500000000',
            'terms_url'           => '/api/v1/pages/terms-conditions',
            'privacy_url'         => '/api/v1/pages/privacy-policy',
            'default_currency'    => $this->settings->get('default_currency', 'SAR'),
            'user_currency'       => $request->attributes->get('user_currency',
                                        $this->settings->get('default_currency', 'SAR')),
            'multi_currency'      => $this->settings->get('multi_currency', false),
            'auto_conversion'     => $this->settings->get('auto_conversion', false),
            'platform_language'   => $this->settings->get('platform_language', 'en'),
            'timezone'            => $this->settings->get('timezone', 'Asia/Riyadh'),
        ], 'App config retrieved successfully.');
    }

    /**
     * GET /api/v1/app/currencies
     * Returns supported currencies with live rates relative to the platform default.
     */
    public function currencies(): JsonResponse
    {
        return $this->successResponse(
            $this->currency->getSupportedCurrencies(),
            'Currencies retrieved successfully.'
        );
    }
}
