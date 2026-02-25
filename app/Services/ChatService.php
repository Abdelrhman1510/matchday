<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Events\ReactionSent;
use App\Events\ViewerCountUpdated;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Get or create public chat room for a match
     */
    public function getOrCreatePublicRoom(int $matchId): ChatRoom
    {
        // Check if match exists with teams
        $match = GameMatch::with(['homeTeam', 'awayTeam'])->findOrFail($matchId);
        
        // Get or create public room
        $room = ChatRoom::firstOrCreate(
            [
                'match_id' => $matchId,
                'branch_id' => null,
                'type' => 'public',
            ],
            [
                'is_active' => true,
                'viewers_count' => 0,
            ]
        );
        
        // Always load relationships to ensure they're present
        $room->load(['match.homeTeam', 'match.awayTeam']);
        
        return $room;
    }
    
    /**
     * Get or create cafe-specific chat room for a match and branch
     */
    public function getOrCreateCafeRoom(User $user, int $matchId, int $branchId): ChatRoom
    {
        // Check if match exists with teams
        $match = GameMatch::with(['homeTeam', 'awayTeam'])->findOrFail($matchId);
        
        // Verify user has a confirmed/checked_in booking at this branch for this match
        $hasBooking = Booking::where('user_id', $user->id)
            ->where('match_id', $matchId)
            ->where('branch_id', $branchId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
            
        if (!$hasBooking) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You must have a booking at this venue to join this chat'
            );
        }
        
        // Get or create cafe room
        $room = ChatRoom::firstOrCreate(
            [
                'match_id' => $matchId,
                'branch_id' => $branchId,
                'type' => 'cafe',
            ],
            [
                'is_active' => true,
                'viewers_count' => 0,
            ]
        );
        
        // Always load relationships to ensure they're present
        $room->load(['match.homeTeam', 'match.awayTeam', 'branch.cafe']);
        
        return $room;
    }
    
    /**
     * Get paginated messages for a room
     */
    public function getRoomMessages(int $roomId, ?string $before = null, int $perPage = 30): LengthAwarePaginator
    {
        $query = ChatMessage::with(['user.fanProfile.favoriteTeam'])
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'desc');
            
        // Filter by before timestamp if provided
        if ($before) {
            $query->where('created_at', '<', $before);
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Send a message to a chat room
     */
    public function sendMessage(User $user, int $roomId, array $data): ChatMessage
    {
        return DB::transaction(function () use ($user, $roomId, $data) {
            // Get room and verify it's active
            $room = ChatRoom::findOrFail($roomId);
            
            if (!$room->is_active) {
                throw new \Exception('This chat room is no longer active');
            }
            
            // Create message
            $message = ChatMessage::create([
                'room_id' => $roomId,
                'user_id' => $user->id,
                'message' => $data['message'],
                'type' => $data['type'] ?? 'text',
            ]);
            
            // Load relationships for broadcasting
            $message->load(['user.fanProfile.favoriteTeam']);
            
            // Broadcast the message
            broadcast(new ChatMessageSent($message))->toOthers();
            
            return $message;
        });
    }
    
    /**
     * Send a reaction to a chat room
     */
    public function sendReaction(User $user, int $roomId, string $emoji): void
    {
        // Validate emoji
        $allowedEmojis = ['heart', 'fire', 'goal', 'clap', 'star'];
        
        if (!in_array($emoji, $allowedEmojis)) {
            throw new \InvalidArgumentException('Invalid emoji. Allowed: ' . implode(', ', $allowedEmojis));
        }
        
        // Get room and verify it's active
        $room = ChatRoom::findOrFail($roomId);
        
        if (!$room->is_active) {
            throw new \Exception('This chat room is no longer active');
        }
        
        // Broadcast the reaction
        broadcast(new ReactionSent($emoji, $user, $roomId))->toOthers();
    }
    
    /**
     * Get viewers count for a room
     */
    public function getViewersCount(int $roomId): int
    {
        $room = ChatRoom::findOrFail($roomId);
        return $room->viewers_count;
    }
    
    /**
     * Get online users in a room (max 20)
     */
    public function getOnlineUsers(int $roomId): array
    {
        // In a real implementation, this would use Reverb's presence channel API
        // For now, we'll return a cached list that can be updated via WebSocket events
        $cacheKey = "chat_room:{$roomId}:online_users";
        
        return Cache::get($cacheKey, []);
    }
    
    /**
     * Update viewers count for a room
     */
    public function updateViewersCount(int $roomId, int $count): void
    {
        $room = ChatRoom::findOrFail($roomId);
        $room->update(['viewers_count' => $count]);
        
        // Broadcast the update
        broadcast(new ViewerCountUpdated($roomId, $count));
    }
    
    /**
     * Add user to online users list
     */
    public function addOnlineUser(int $roomId, User $user): void
    {
        $cacheKey = "chat_room:{$roomId}:online_users";
        $onlineUsers = Cache::get($cacheKey, []);
        
        // Check if user is already in the list
        $exists = collect($onlineUsers)->contains('user_id', $user->id);
        
        if (!$exists) {
            $onlineUsers[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'joined_at' => now()->toIso8601String(),
            ];
            
            // Keep only last 20 users
            $onlineUsers = array_slice($onlineUsers, -20);
            
            Cache::put($cacheKey, $onlineUsers, now()->addHours(4));
        }
    }
    
    /**
     * Remove user from online users list
     */
    public function removeOnlineUser(int $roomId, int $userId): void
    {
        $cacheKey = "chat_room:{$roomId}:online_users";
        $onlineUsers = Cache::get($cacheKey, []);
        
        $onlineUsers = array_filter($onlineUsers, function ($user) use ($userId) {
            return $user['user_id'] !== $userId;
        });
        
        Cache::put($cacheKey, array_values($onlineUsers), now()->addHours(4));
    }
}
