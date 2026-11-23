<?php

namespace App\Services\Payment;

use App\Exceptions\MoyasarApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin wrapper over the Moyasar REST API. Uses the SECRET key (server-only) with
 * HTTP Basic auth: base64("sk_xxx:"). Never logs tokens or PANs.
 */
class MoyasarClient
{
    private string $baseUrl;
    private string $secretKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.moyasar.base_url'), '/');
        $this->secretKey = (string) config('services.moyasar.secret_key');
    }

    private function request()
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Moyasar secret key is not configured.');
        }

        // Basic auth with an empty password: base64("sk_xxx:").
        return Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->timeout(20);
    }

    /** GET /payments/{id} */
    public function getPayment(string $paymentId): array
    {
        return $this->handle(
            $this->request()->get("{$this->baseUrl}/payments/{$paymentId}"),
            'fetch payment'
        );
    }

    /** GET /tokens/{token} — authoritative card metadata (brand, last_four, month, year, …). */
    public function getToken(string $token): array
    {
        return $this->handle(
            $this->request()->get("{$this->baseUrl}/tokens/{$token}"),
            'fetch token'
        );
    }

    /** POST /payments/{id}/void — release a manual (uncaptured) authorization. */
    public function voidPayment(string $paymentId): array
    {
        return $this->handle(
            $this->request()->post("{$this->baseUrl}/payments/{$paymentId}/void"),
            'void payment'
        );
    }

    /** POST /payments/{id}/refund — reverse a captured payment. */
    public function refundPayment(string $paymentId, ?int $amount = null): array
    {
        $body = $amount !== null ? ['amount' => $amount] : [];

        return $this->handle(
            $this->request()->post("{$this->baseUrl}/payments/{$paymentId}/refund", $body),
            'refund payment'
        );
    }

    /**
     * POST /payments — charge a stored card-on-file token.
     * @param int $amount smallest currency unit (halalas); SAR 25.00 = 2500
     */
    public function createTokenPayment(string $token, int $amount, string $currency, string $description, array $metadata = [], ?string $callbackUrl = null): array
    {
        $body = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'source' => ['type' => 'token', 'token' => $token],
        ];
        if ($metadata) {
            $body['metadata'] = $metadata;
        }
        if ($callbackUrl) {
            $body['callback_url'] = $callbackUrl;
        }

        return $this->handle(
            $this->request()->post("{$this->baseUrl}/payments", $body),
            'create token payment'
        );
    }

    /** POST /payouts. Safe to repeat with the same given_id. */
    public function createPayout(array $body): array
    {
        if (!config('services.moyasar.payouts_enabled')) {
            throw new RuntimeException('Moyasar payouts are disabled (MOYASAR_PAYOUTS_ENABLED).');
        }

        return $this->handlePayout($this->request()->post("{$this->baseUrl}/payouts", $body), 'create payout');
    }

    /**
     * Fetch one payout, or null if Moyasar has no such payout. The API reference
     * writes this path as /payout/:id while every other endpoint is /payouts, so
     * both are tried.
     */
    public function getPayout(string $id): ?array
    {
        foreach (["payouts/{$id}", "payout/{$id}"] as $path) {
            $response = $this->request()->get("{$this->baseUrl}/{$path}");
            if ($response->status() !== 404) {
                return $this->handlePayout($response, 'fetch payout');
            }
        }

        return null;
    }

    /** GET /payouts?page=N, for the nightly reconciliation. */
    public function listPayouts(int $page = 1): array
    {
        return $this->handlePayout($this->request()->get("{$this->baseUrl}/payouts", ['page' => $page]), 'list payouts');
    }

    private function handlePayout($response, string $action): array
    {
        if ($response->successful()) {
            return (array) $response->json();
        }

        $body = (array) $response->json();
        $message = (string) ($body['message'] ?? "Moyasar {$action} failed.");
        $type = $body['type'] ?? null;
        Log::warning("Moyasar {$action} failed", ['status' => $response->status(), 'type' => $type, 'message' => $message]);

        throw new MoyasarApiException($message, $response->status(), is_string($type) ? $type : null);
    }

    /**
     * Return the decoded JSON on success, or throw with the Moyasar-supplied
     * message. We surface Moyasar's own message (it's customer-safe) and log the
     * status without any card data.
     */
    private function handle($response, string $action): array
    {
        if ($response->successful()) {
            return (array) $response->json();
        }

        $body = (array) $response->json();
        $message = $body['message'] ?? "Moyasar {$action} failed.";

        Log::warning("Moyasar {$action} failed", [
            'status' => $response->status(),
            'message' => $message,
        ]);

        throw new RuntimeException($message);
    }
}
