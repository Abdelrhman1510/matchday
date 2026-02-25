<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc');

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get count of unread notifications
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Unread count retrieved successfully',
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a specific notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = \App\Models\Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to notification',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Get notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $settings = $request->user()->notification_settings ?? [
            'email_notifications' => true,
            'push_notifications' => true,
            'booking_reminders' => true,
            'match_updates' => true,
            'promotions' => true,
            'chat_messages' => true,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Notification settings retrieved',
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
            'booking_reminders' => 'sometimes|boolean',
            'match_updates' => 'sometimes|boolean',
            'promotions' => 'sometimes|boolean',
            'chat_messages' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get current settings or defaults
        $currentSettings = $request->user()->notification_settings ?? [
            'email_notifications' => true,
            'push_notifications' => true,
            'booking_reminders' => true,
            'match_updates' => true,
            'promotions' => true,
            'chat_messages' => true,
        ];

        // Merge with provided values
        $settings = array_merge($currentSettings, $request->only([
            'email_notifications',
            'push_notifications',
            'booking_reminders',
            'match_updates',
            'promotions',
            'chat_messages',
        ]));

        $request->user()->update([
            'notification_settings' => $settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'data' => $settings,
        ]);
    }
}
