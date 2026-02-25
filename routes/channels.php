<?php

use App\Models\Booking;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Chat room presence channel
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    // Get the chat room with match and branch
    $room = ChatRoom::with(['match', 'branch'])->find($roomId);
    
    if (!$room || !$room->is_active) {
        return false;
    }
    
    // If it's a public room (no branch_id), anyone authenticated can join
    if ($room->type === 'public' && $room->branch_id === null) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    
    // If it's a cafe-specific room, check if user has a confirmed/checked_in booking
    if ($room->type === 'cafe' && $room->branch_id !== null) {
        $hasBooking = Booking::where('user_id', $user->id)
            ->where('match_id', $room->match_id)
            ->where('branch_id', $room->branch_id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->exists();
            
        if (!$hasBooking) {
            return false;
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    
    return false;
});

// User notification private channel
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
