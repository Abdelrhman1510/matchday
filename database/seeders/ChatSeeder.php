<?php

namespace Database\Seeders;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $liveMatches = GameMatch::where('status', 'live')->get();
        $fans = User::where('role', 'fan')->get();

        foreach ($liveMatches as $match) {
            // Create public chat room for the match
            $publicRoom = ChatRoom::create([
                'match_id' => $match->id,
                'type' => 'public',
                'is_active' => true,
                'viewers_count' => rand(15, 45),
            ]);

            // Create cafe-specific chat room
            $cafeRoom = ChatRoom::create([
                'match_id' => $match->id,
                'type' => 'cafe',
                'is_active' => true,
                'viewers_count' => rand(5, 15),
            ]);

            // Add messages to public room
            $messages = [
                ['user' => 0, 'message' => "Let's go! Can't wait for this match! ⚽", 'type' => 'text'],
                ['user' => 1, 'message' => 'Who do you think will win?', 'type' => 'text'],
                ['user' => 2, 'message' => 'Home team all the way! 💪', 'type' => 'text'],
                ['user' => 0, 'message' => '🔥', 'type' => 'emoji'],
                ['user' => 3, 'message' => 'This is going to be intense!', 'type' => 'text'],
                ['user' => 1, 'message' => 'Great atmosphere here at the cafe!', 'type' => 'text'],
                ['user' => 4, 'message' => '👏👏👏', 'type' => 'emoji'],
                ['user' => 2, 'message' => 'Amazing game so far!', 'type' => 'text'],
                ['user' => 0, 'message' => 'GOOOAAAAL!!! ⚽🎉', 'type' => 'text'],
                ['user' => 3, 'message' => 'What a shot!', 'type' => 'text'],
            ];

            foreach ($messages as $index => $msg) {
                ChatMessage::create([
                    'room_id' => $publicRoom->id,
                    'user_id' => $fans[$msg['user']]->id,
                    'message' => $msg['message'],
                    'type' => $msg['type'],
                    'created_at' => now()->subMinutes(30 - ($index * 2)),
                ]);
            }

            // Add messages to cafe room
            $cafeMessages = [
                ['user' => 0, 'message' => 'The view from here is perfect!', 'type' => 'text'],
                ['user' => 1, 'message' => 'Best seats in the house 🏆', 'type' => 'text'],
                ['user' => 2, 'message' => 'Food is amazing too!', 'type' => 'text'],
                ['user' => 3, 'message' => 'Anyone want to order appetizers?', 'type' => 'text'],
                ['user' => 0, 'message' => "I'm in! Let's get the platter", 'type' => 'text'],
            ];

            foreach ($cafeMessages as $index => $msg) {
                ChatMessage::create([
                    'room_id' => $cafeRoom->id,
                    'user_id' => $fans[$msg['user']]->id,
                    'message' => $msg['message'],
                    'type' => $msg['type'],
                    'created_at' => now()->subMinutes(25 - ($index * 3)),
                ]);
            }
        }

        $this->command->info('Chat rooms and messages seeded successfully!');
        $this->command->info('Created public and cafe chat rooms for live matches.');
    }
}
