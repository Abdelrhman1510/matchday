<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\GameMatch;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatRoomResource;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Get or create a general public chat room
     * GET /api/v1/chat/rooms/public
     */
    public function getOrCreatePublicRoomGeneral(Request $request): JsonResponse
    {
        try {
            $room = ChatRoom::firstOrCreate(
                ['type' => 'public', 'name' => 'General Public Chat'],
                ['is_active' => true, 'viewers_count' => 0]
            );

            // Auto-join user to the room
            $request->user()->chatRooms()->syncWithoutDetaching([$room->id]);

            return response()->json([
                'success' => true,
                'message' => 'Public chat room retrieved successfully.',
                'data' => [
                    'id' => $room->id,
                    'type' => $room->type,
                    'name' => $room->name ?? 'General Public Chat',
                    'is_active' => $room->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cafe chat room by cafe ID
     * GET /api/v1/chat/rooms/cafe/{cafeId}
     */
    public function getCafeRoomByCafe(Request $request, int $cafeId): JsonResponse
    {
        $cafe = Cafe::find($cafeId);
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'Cafe not found.',
            ], 404);
        }

        // Check if user has a booking at this cafe (via branch directly or via match's branch)
        $user = $request->user();
        $hasBooking = Booking::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($cafeId) {
                $q->whereHas('branch', function ($b) use ($cafeId) {
                    $b->where('cafe_id', $cafeId);
                })->orWhereHas('match', function ($m) use ($cafeId) {
                    $m->whereHas('branch', function ($b) use ($cafeId) {
                        $b->where('cafe_id', $cafeId);
                    });
                });
            })
            ->exists();

        if (!$hasBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You must have a booking at this cafe to access the chat room.',
                'errors' => ['booking' => ['No active booking found at this cafe.']],
            ], 403);
        }

        $room = ChatRoom::firstOrCreate(
            ['type' => 'cafe', 'cafe_id' => $cafeId],
            ['name' => $cafe->name . ' Chat', 'is_active' => true, 'viewers_count' => 0]
        );

        // Auto-join user to the room
        $request->user()->chatRooms()->syncWithoutDetaching([$room->id]);

        return response()->json([
            'success' => true,
            'message' => 'Cafe chat room retrieved successfully.',
            'data' => [
                'id' => $room->id,
                'type' => $room->type,
                'cafe_id' => $room->cafe_id,
                'name' => $room->name,
                'is_active' => $room->is_active,
            ],
        ]);
    }

    /**
     * Get match-specific chat room
     * GET /api/v1/chat/rooms/match/{matchId}
     */
    public function getMatchRoom(Request $request, int $matchId): JsonResponse
    {
        $match = GameMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
            ], 404);
        }

        $room = ChatRoom::firstOrCreate(
            ['type' => 'match', 'match_id' => $matchId],
            ['name' => 'Match #' . $matchId . ' Chat', 'is_active' => true, 'viewers_count' => 0]
        );

        // Auto-join user to the room
        $request->user()->chatRooms()->syncWithoutDetaching([$room->id]);

        return response()->json([
            'success' => true,
            'message' => 'Match chat room retrieved successfully',
            'data' => [
                'id' => $room->id,
                'type' => $room->type,
                'match_id' => $room->match_id,
                'name' => $room->name,
                'is_active' => $room->is_active,
            ],
        ]);
    }

    /**
     * List user's chat rooms
     * GET /api/v1/chat/rooms
     */
    public function listUserRooms(Request $request): JsonResponse
    {
        $rooms = $request->user()->chatRooms()->get();

        return response()->json([
            'success' => true,
            'message' => 'Chat rooms retrieved successfully.',
            'data' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'type' => $room->type,
                    'name' => $room->name ?? ($room->type === 'public' ? 'Public Chat' : 'Chat Room'),
                    'is_active' => $room->is_active,
                ];
            }),
        ]);
    }

    /**
     * Get or create public chat room for a match
     * GET /api/v1/chat/rooms/{matchId}
     */
    public function getPublicRoom(Request $request, int $matchId): JsonResponse
    {
        try {
            $room = $this->chatService->getOrCreatePublicRoom($matchId);

            // Auto-join user to the room
            $request->user()->chatRooms()->syncWithoutDetaching([$room->id]);

            return response()->json([
                'success' => true,
                'message' => 'Public chat room retrieved successfully.',
                'data' => new ChatRoomResource($room),
                'meta' => (object)[],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get or create cafe-specific chat room for a match and branch
     * GET /api/v1/chat/rooms/{matchId}/branch/{branchId}
     */
    public function getCafeRoom(Request $request, int $matchId, int $branchId): JsonResponse
    {
        try {
            $room = $this->chatService->getOrCreateCafeRoom($request->user(), $matchId, $branchId);

            // Auto-join user to the room
            $request->user()->chatRooms()->syncWithoutDetaching([$room->id]);

            return response()->json([
                'success' => true,
                'message' => 'Cafe chat room retrieved successfully.',
                'data' => new ChatRoomResource($room),
                'meta' => (object)[],
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Match or branch not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat room: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get paginated messages for a chat room
     * GET /api/v1/chat/rooms/{roomId}/messages
     */
    public function getMessages(Request $request, int $roomId): JsonResponse
    {
        $room = ChatRoom::find($roomId);
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chat room not found.',
            ], 404);
        }

        $perPage = $request->input('per_page', 30);

        $messages = ChatMessage::where('room_id', $roomId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // If per_page is specified, return paginated structure
        if ($request->has('per_page')) {
            return response()->json([
                'success' => true,
                'message' => 'Messages retrieved successfully.',
                'data' => [
                    'data' => $messages->items() ? collect($messages->items())->map(function ($msg) {
                        return [
                            'id' => $msg->id,
                            'message' => $msg->message,
                            'user' => $msg->user ? [
                                'id' => $msg->user->id,
                                'name' => $msg->user->name,
                            ] : null,
                            'created_at' => $msg->created_at->toISOString(),
                        ];
                    }) : [],
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'last_page' => $messages->lastPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messages retrieved successfully.',
            'data' => collect($messages->items())->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'user' => $msg->user ? [
                        'id' => $msg->user->id,
                        'name' => $msg->user->name,
                    ] : null,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            }),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Send a message to a chat room
     * POST /api/v1/chat/rooms/{roomId}/messages
     */
    public function sendMessage(Request $request, int $roomId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'type' => ['sometimes', 'string', 'in:text,emoji'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $room = ChatRoom::find($roomId);
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Chat room not found.',
            ], 404);
        }

        // Check if user is a member of this room
        // For public/match rooms, auto-join; for cafe rooms, require prior access
        $user = $request->user();
        if (!$user->chatRooms()->where('chat_room_id', $room->id)->exists()) {
            if (in_array($room->type, ['public', 'match'])) {
                // Auto-join public and match rooms on first message
                $user->chatRooms()->syncWithoutDetaching([$room->id]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this chat room.',
                ], 403);
            }
        }

        $message = ChatMessage::create([
            'room_id' => $roomId,
            'user_id' => $user->id,
            'message' => $request->input('message'),
            'type' => $request->input('type', 'text'),
        ]);

        $message->load('user');

        // Broadcast the message to the chat room in real time
        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'user' => $message->user ? [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ] : null,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Send a reaction to a chat room
     * POST /api/v1/chat/rooms/{roomId}/reaction
     */
    public function sendReaction(Request $request, int $roomId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emoji' => ['required', 'string', 'in:heart,fire,goal,clap,star'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->chatService->sendReaction(
                $request->user(),
                $roomId,
                $request->input('emoji')
            );

            return response()->json([
                'success' => true,
                'message' => 'Reaction sent successfully.',
                'data' => ['emoji' => $request->input('emoji')],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get viewers count for a chat room
     * GET /api/v1/chat/rooms/{roomId}/viewers
     */
    public function getViewersCount(int $roomId): JsonResponse
    {
        try {
            $count = $this->chatService->getViewersCount($roomId);

            return response()->json([
                'success' => true,
                'message' => 'Viewers count retrieved successfully.',
                'data' => ['viewers_count' => $count],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chat room not found.',
            ], 404);
        }
    }

    /**
     * Get online users in a chat room
     * GET /api/v1/chat/rooms/{roomId}/online-users
     */
    public function getOnlineUsers(int $roomId): JsonResponse
    {
        try {
            $users = $this->chatService->getOnlineUsers($roomId);

            return response()->json([
                'success' => true,
                'message' => 'Online users retrieved successfully.',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chat room not found.',
            ], 404);
        }
    }
}
