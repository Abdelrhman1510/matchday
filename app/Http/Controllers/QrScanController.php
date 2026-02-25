<?php

namespace App\Http\Controllers;

use App\Services\QrScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QrScanController extends Controller
{
    protected QrScanService $qrService;

    public function __construct(QrScanService $qrService)
    {
        $this->qrService = $qrService;
    }

    // =========================================
    // HELPER: Get owner's cafe
    // =========================================

    protected function getOwnerCafe(Request $request)
    {
        return $request->user()->ownedCafes()->first();
    }

    // =========================================
    // 6. SCAN QR CODE (text)
    // POST /api/v1/cafe-admin/scan-qr
    // Permission: scan-qr
    // =========================================

    public function scan(Request $request)
    {
        if (!$request->user()->can('scan-qr')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to scan QR codes.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'qr_code' => 'required_without:qr_data|string|max:500',
            'qr_data' => 'required_without:qr_code|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $qrInput = $request->input('qr_code') ?? $request->input('qr_data');
        $cafe = $this->getOwnerCafe($request);

        // If no cafe, try to find booking directly - if not found, return 422
        if (!$cafe) {
            $booking = \App\Models\Booking::where('booking_code', $qrInput)
                ->orWhere('qr_code', $qrInput)
                ->first();
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found for this QR code.',
                    'errors' => ['qr_data' => ['Invalid QR code. No booking found.']],
                ], 422);
            }
            // If booking found, get cafe from booking's branch
            $cafe = $booking->branch?->cafe ?? ($booking->match?->branch?->cafe);
            if (!$cafe) {
                return response()->json([
                    'success' => false,
                    'message' => 'No cafe found.',
                ], 404);
            }
        }

        $result = $this->qrService->scanQrCode(
            $qrInput,
            $cafe,
            $request->user()->id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ], $result['status']);
        }

        $booking = $result['booking'];

        $loyaltyTier = $booking->user?->loyaltyCard?->tier ?? 'bronze';

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'already_checked_in' => $result['already_checked_in'],
                'check_in_time' => $booking->checked_in_at?->toIso8601String(),
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'guests_count' => $booking->guests_count,
                    'customer' => [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'phone' => $booking->user->phone,
                        'avatar' => $this->formatAvatar($booking->user->avatar),
                        'loyalty_tier' => $loyaltyTier,
                    ],
                    'match' => $booking->match ? [
                        'id' => $booking->match->id,
                        'match_date' => $booking->match->match_date?->format('Y-m-d'),
                        'kick_off' => $booking->match->kick_off,
                        'status' => $booking->match->status,
                        'home_team' => $booking->match->homeTeam ? [
                            'name' => $booking->match->homeTeam->name,
                            'short_name' => $booking->match->homeTeam->short_name,
                            'logo' => $this->formatTeamLogo($booking->match->homeTeam->logo),
                        ] : null,
                        'away_team' => $booking->match->awayTeam ? [
                            'name' => $booking->match->awayTeam->name,
                            'short_name' => $booking->match->awayTeam->short_name,
                            'logo' => $this->formatTeamLogo($booking->match->awayTeam->logo),
                        ] : null,
                    ] : null,
                    'seats' => $booking->seats->map(fn($seat) => [
                        'label' => $seat->label,
                        'section' => $seat->section?->name,
                    ])->toArray(),
                ],
                'is_vip' => in_array($loyaltyTier, ['gold', 'platinum']),
            ],
        ]);
    }

    // =========================================
    // 7. SCAN QR CODE (image upload)
    // POST /api/v1/cafe-admin/scan-qr/upload
    // Permission: scan-qr
    // =========================================

    public function upload(Request $request)
    {
        if (!$request->user()->can('scan-qr')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to scan QR codes.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,bmp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        // Save temp file for QR decode
        $imagePath = $request->file('image')->getRealPath();

        $decodedText = $this->qrService->decodeQrImage($imagePath);

        if (!$decodedText) {
            return response()->json([
                'success' => false,
                'message' => 'Could not decode QR code from the uploaded image. Please ensure the image contains a clear QR code, or use the text-based scan endpoint instead.',
            ], 422);
        }

        // Now scan the decoded text
        $result = $this->qrService->scanQrCode(
            $decodedText,
            $cafe,
            $request->user()->id
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        $booking = $result['booking'];
        $loyaltyTier = $booking->user?->loyaltyCard?->tier ?? 'bronze';

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'already_checked_in' => $result['already_checked_in'],
                'decoded_text' => $decodedText,
                'booking' => [
                    'id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'guests_count' => $booking->guests_count,
                    'customer' => [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'phone' => $booking->user->phone,
                    ],
                    'seats' => $booking->seats->map(fn($seat) => [
                        'label' => $seat->label,
                        'section' => $seat->section?->name,
                    ])->toArray(),
                ],
                'is_vip' => in_array($loyaltyTier, ['gold', 'platinum']),
            ],
        ]);
    }

    // =========================================
    // 8. RECENT SCANS
    // GET /api/v1/cafe-admin/scan-qr/recent
    // Permission: scan-qr
    // =========================================

    public function recent(Request $request)
    {
        if (!$request->user()->can('scan-qr')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to scan QR codes.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $scans = $this->qrService->getRecentScans($cafe, 10);

        return response()->json([
            'success' => true,
            'message' => 'Recent scans retrieved successfully',
            'data' => $scans,
        ]);
    }

    // =========================================
    // 9. SCAN STATS
    // GET /api/v1/cafe-admin/scan-qr/stats
    // Permission: scan-qr
    // =========================================

    public function stats(Request $request)
    {
        if (!$request->user()->can('scan-qr')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to scan QR codes.',
            ], 403);
        }

        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $stats = $this->qrService->getScanStats($cafe);

        return response()->json([
            'success' => true,
            'message' => "Today's QR scan statistics",
            'data' => $stats,
        ]);
    }

    // =========================================
    // HELPERS
    // =========================================

    private function formatTeamLogo($logo): ?array
    {
        if (!$logo) return null;
        if (is_array($logo)) {
            return [
                'original' => $logo['original'] ?? $logo['url'] ?? null,
                'medium' => $logo['medium'] ?? $logo['original'] ?? $logo['url'] ?? null,
                'thumbnail' => $logo['thumbnail'] ?? $logo['original'] ?? $logo['url'] ?? null,
            ];
        }
        return ['original' => $logo, 'medium' => $logo, 'thumbnail' => $logo];
    }

    private function formatAvatar($avatar): ?array
    {
        if (!$avatar) return null;
        if (is_array($avatar)) {
            return [
                'original' => $avatar['original'] ?? $avatar['url'] ?? null,
                'medium' => $avatar['medium'] ?? $avatar['original'] ?? $avatar['url'] ?? null,
                'thumbnail' => $avatar['thumbnail'] ?? $avatar['original'] ?? $avatar['url'] ?? null,
            ];
        }
        return ['original' => $avatar, 'medium' => $avatar, 'thumbnail' => $avatar];
    }
}
