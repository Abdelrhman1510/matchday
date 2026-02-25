<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\QrScanLog;
use Illuminate\Support\Facades\Cache;

class QrScanService
{
    /**
     * Find a booking by QR code string and validate ownership.
     */
    public function scanQrCode(string $qrCode, Cafe $cafe, int $scannedBy): array
    {
        $startTime = microtime(true);

        $booking = Booking::where('booking_code', $qrCode)
            ->orWhere('qr_code', $qrCode)
            ->first();

        if (!$booking) {
            $this->logScan($cafe->id, $scannedBy, null, $qrCode, 'not_found', 'Booking not found.', $startTime);
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Booking not found for this QR code.',
                'errors' => ['qr_data' => ['Invalid QR code. No booking found.']],
            ];
        }

        // Validate belongs to this café (check both direct branch_id and match's branch_id)
        $branchIds = $cafe->branches()->pluck('id')->toArray();
        $matchBranchId = $booking->match ? $booking->match->branch_id : null;
        if (!in_array($booking->branch_id, $branchIds) && !in_array($matchBranchId, $branchIds)) {
            $this->logScan($cafe->id, $scannedBy, $booking->id, $qrCode, 'wrong_cafe', 'Booking belongs to another cafe.', $startTime);
            return [
                'success' => false,
                'status' => 403,
                'message' => 'This booking does not belong to your café.',
            ];
        }

        // Check status
        if ($booking->status === 'checked_in') {
            $this->logScan($cafe->id, $scannedBy, $booking->id, $qrCode, 'already_checked_in', null, $startTime);

            $booking->load([
                'user:id,name,phone,avatar',
                'user.loyaltyCard:id,user_id,tier',
                'match:id,home_team_id,away_team_id,match_date,kick_off,status',
                'match.homeTeam:id,name,short_name,logo',
                'match.awayTeam:id,name,short_name,logo',
                'seats:id,label,section_id',
                'seats.section:id,name',
            ]);

            return [
                'success' => true,
                'already_checked_in' => true,
                'message' => 'This booking has already been checked in.',
                'booking' => $booking,
            ];
        }

        if ($booking->status === 'cancelled') {
            $this->logScan($cafe->id, $scannedBy, $booking->id, $qrCode, 'invalid_status', "Status: {$booking->status}", $startTime);
            return [
                'success' => false,
                'status' => 409,
                'message' => 'This booking has been cancelled.',
            ];
        }

        if (!in_array($booking->status, ['confirmed', 'pending'])) {
            $this->logScan($cafe->id, $scannedBy, $booking->id, $qrCode, 'invalid_status', "Status: {$booking->status}", $startTime);
            return [
                'success' => false,
                'status' => 409,
                'message' => "Booking status '{$booking->status}' is not valid for check-in.",
            ];
        }

        // Log success
        $this->logScan($cafe->id, $scannedBy, $booking->id, $qrCode, 'success', null, $startTime);

        // Perform check-in
        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        // Load full data for response
        $booking->load([
            'user:id,name,phone,avatar',
            'user.loyaltyCard:id,user_id,tier',
            'match:id,home_team_id,away_team_id,match_date,kick_off,status',
            'match.homeTeam:id,name,short_name,logo',
            'match.awayTeam:id,name,short_name,logo',
            'seats:id,label,section_id',
            'seats.section:id,name',
        ]);

        return [
            'success' => true,
            'already_checked_in' => false,
            'message' => 'Booking found.',
            'booking' => $booking,
        ];
    }

    /**
     * Decode QR from uploaded image.
     * Returns the decoded string or null.
     */
    public function decodeQrImage(string $imagePath): ?string
    {
        // Try using the zxing PHP library if available
        // Fallback: use Python zbarlight or command-line tool
        // For production, integrate a QR library. Here we attempt basic decode.

        // Attempt 1: Use PHP QR reader if the class exists
        if (class_exists(\Zxing\QrReader::class)) {
            try {
                $qrReader = new \Zxing\QrReader($imagePath);
                $text = $qrReader->text();
                if ($text && $text !== '') {
                    return trim($text);
                }
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // Attempt 2: chillerlan/php-qrcode reader
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                $reader = new \chillerlan\QRCode\QRCode();
                return $reader->readFromFile($imagePath);
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // If no QR library available, return null and let the controller handle
        return null;
    }

    /**
     * Get recent scans for a café.
     */
    public function getRecentScans(Cafe $cafe, int $limit = 10): array
    {
        $scans = QrScanLog::where('cafe_id', $cafe->id)
            ->with('booking:id,booking_code,status')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($scan) {
                return [
                    'id' => $scan->id,
                    'booking_code' => $scan->booking_code ?? $scan->booking?->booking_code ?? 'unknown',
                    'scanned_at' => $scan->created_at->toIso8601String(),
                    'result' => $scan->result,
                    'status' => $scan->booking?->status ?? null,
                    'error_message' => $scan->error_message,
                ];
            })
            ->toArray();

        return $scans;
    }

    /**
     * Today's scan stats for a café.
     */
    public function getScanStats(Cafe $cafe): array
    {
        $cacheKey = "qr_scan_stats_{$cafe->id}_" . now()->toDateString();

        return Cache::remember($cacheKey, 60, function () use ($cafe) {
            $todayScans = QrScanLog::where('cafe_id', $cafe->id)
                ->whereDate('created_at', now()->toDateString())
                ->get();

            $totalScans = $todayScans->count();
            $successScans = $todayScans->where('result', 'success')->count();
            $avgSpeed = $totalScans > 0
                ? round($todayScans->avg('processing_ms') / 1000, 2)
                : 0;

            return [
                'today_scans' => $totalScans,
                'success_rate' => $totalScans > 0
                    ? round(($successScans / $totalScans) * 100, 1)
                    : 0,
                'avg_speed_seconds' => $avgSpeed,
            ];
        });
    }

    /**
     * Log a QR scan attempt.
     */
    private function logScan(
        int $cafeId,
        int $scannedBy,
        ?int $bookingId,
        string $bookingCode,
        string $result,
        ?string $errorMessage,
        float $startTime
    ): void {
        $processingMs = (int) ((microtime(true) - $startTime) * 1000);

        QrScanLog::create([
            'cafe_id' => $cafeId,
            'scanned_by' => $scannedBy,
            'booking_id' => $bookingId,
            'booking_code' => $bookingCode,
            'result' => $result,
            'error_message' => $errorMessage,
            'processing_ms' => $processingMs,
        ]);

        // Bust stats cache
        Cache::forget("qr_scan_stats_{$cafeId}_" . now()->toDateString());
    }
}
