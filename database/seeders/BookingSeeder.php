<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingPlayer;
use App\Models\GameMatch;
use App\Models\User;
use App\Models\Seat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $fans = User::where('role', 'fan')->get();
        $matches = GameMatch::all();

        // Booking 1: Ahmed Hassan - Live match - Checked in
        $match1 = GameMatch::where('status', 'live')->first();
        $seats1 = Seat::whereHas('section', fn($q) => $q->where('branch_id', $match1->branch_id))
            ->limit(3)->get();
        
        $booking1 = Booking::create([
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => $fans[0]->id,
            'match_id' => $match1->id,
            'branch_id' => $match1->branch_id,
            'guests_count' => 3,
            'status' => 'checked_in',
            'special_requests' => 'Table near the main screen please',
            'subtotal' => 135.00,
            'service_fee' => 13.50,
            'total_amount' => 148.50,
            'currency' => 'SAR',
            'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
            'checked_in_at' => now()->subMinutes(40),
        ]);
        $booking1->seats()->attach($seats1->pluck('id'));
        
        BookingPlayer::create([
            'booking_id' => $booking1->id,
            'user_id' => $fans[0]->id,
            'name' => $fans[0]->name,
            'position' => 'Captain',
            'is_captain' => true,
        ]);
        BookingPlayer::create([
            'booking_id' => $booking1->id,
            'name' => 'Khalid Ahmed',
            'position' => 'Friend',
            'is_captain' => false,
        ]);
        BookingPlayer::create([
            'booking_id' => $booking1->id,
            'name' => 'Fahad Saeed',
            'position' => 'Friend',
            'is_captain' => false,
        ]);

        // Booking 2: Live match - Confirmed
        $seats2 = Seat::whereHas('section', fn($q) => $q->where('branch_id', $match1->branch_id))
            ->whereNotIn('id', $seats1->pluck('id'))
            ->limit(2)->get();
        
        $booking2 = Booking::create([
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => $fans[1]->id,
            'match_id' => $match1->id,
            'branch_id' => $match1->branch_id,
            'guests_count' => 2,
            'status' => 'confirmed',
            'subtotal' => 90.00,
            'service_fee' => 9.00,
            'total_amount' => 99.00,
            'currency' => 'SAR',
            'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
        ]);
        $booking2->seats()->attach($seats2->pluck('id'));

        // Booking 3: Second live match - Checked in
        $match2 = GameMatch::where('status', 'live')->skip(1)->first();
        $seats3 = Seat::whereHas('section', fn($q) => $q->where('branch_id', $match2->branch_id))
            ->limit(4)->get();
        
        $booking3 = Booking::create([
            'booking_code' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => $fans[2]->id,
            'match_id' => $match2->id,
            'branch_id' => $match2->branch_id,
            'guests_count' => 4,
            'status' => 'checked_in',
            'special_requests' => 'Birthday celebration - need extra plates',
            'subtotal' => 180.00,
            'service_fee' => 18.00,
            'total_amount' => 198.00,
            'currency' => 'SAR',
            'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
            'checked_in_at' => now()->subMinutes(25),
        ]);
        $booking3->seats()->attach($seats3->pluck('id'));

        // Bookings 4-8: Upcoming matches - Confirmed
        $upcomingMatches = GameMatch::where('status', 'upcoming')->take(4)->get();
        
        foreach ($upcomingMatches as $index => $match) {
            $numSeats = rand(2, 4);
            $seats = Seat::whereHas('section', fn($q) => $q->where('branch_id', $match->branch_id))
                ->limit($numSeats)->get();
            
            $subtotal = $match->price_per_seat * $numSeats;
            $serviceFee = $subtotal * 0.10;
            
            Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(8)),
                'user_id' => $fans[$index % count($fans)]->id,
                'match_id' => $match->id,
                'branch_id' => $match->branch_id,
                'guests_count' => $numSeats,
                'status' => 'confirmed',
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $subtotal + $serviceFee,
                'currency' => 'SAR',
                'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
            ])->seats()->attach($seats->pluck('id'));
        }

        // Bookings 9-11: Finished matches - Checked in
        $finishedMatches = GameMatch::where('status', 'finished')->take(3)->get();
        
        foreach ($finishedMatches as $index => $match) {
            $numSeats = rand(1, 3);
            $seats = Seat::whereHas('section', fn($q) => $q->where('branch_id', $match->branch_id))
                ->limit($numSeats)->get();
            
            $subtotal = $match->price_per_seat * $numSeats;
            $serviceFee = $subtotal * 0.10;
            
            // Create proper datetime from match_date and kick_off
            $matchDateTime = \Carbon\Carbon::parse($match->match_date)->format('Y-m-d') . ' ' . (is_object($match->kick_off) ? $match->kick_off->format('H:i:s') : $match->kick_off);
            
            Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(8)),
                'user_id' => $fans[$index]->id,
                'match_id' => $match->id,
                'branch_id' => $match->branch_id,
                'guests_count' => $numSeats,
                'status' => 'checked_in',
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $subtotal + $serviceFee,
                'currency' => 'SAR',
                'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
                'checked_in_at' => $matchDateTime,
            ])->seats()->attach($seats->pluck('id'));
        }

        // Bookings 12-13: Pending bookings
        $upcomingMatch = GameMatch::where('status', 'upcoming')->skip(2)->first();
        if ($upcomingMatch) {
            $seats = Seat::whereHas('section', fn($q) => $q->where('branch_id', $upcomingMatch->branch_id))
                ->limit(2)->get();
            
            Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(8)),
                'user_id' => $fans[3]->id,
                'match_id' => $upcomingMatch->id,
                'branch_id' => $upcomingMatch->branch_id,
                'guests_count' => 2,
                'status' => 'pending',
                'subtotal' => 70.00,
                'service_fee' => 7.00,
                'total_amount' => 77.00,
                'currency' => 'SAR',
                'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
            ])->seats()->attach($seats->pluck('id'));
        }

        // Bookings 14-15: Cancelled bookings
        $cancelledMatch = GameMatch::where('status', 'upcoming')->skip(3)->first();
        if ($cancelledMatch) {
            $seats = Seat::whereHas('section', fn($q) => $q->where('branch_id', $cancelledMatch->branch_id))
                ->limit(3)->get();
            
            Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(8)),
                'user_id' => $fans[4]->id,
                'match_id' => $cancelledMatch->id,
                'branch_id' => $cancelledMatch->branch_id,
                'guests_count' => 3,
                'status' => 'cancelled',
                'special_requests' => 'Window seat preferred',
                'subtotal' => 105.00,
                'service_fee' => 10.50,
                'total_amount' => 115.50,
                'currency' => 'SAR',
                'qr_code' => 'qr_codes/' . Str::random(16) . '.png',
                'cancelled_at' => now()->subHours(12),
            ])->seats()->attach($seats->pluck('id'));
        }

        $this->command->info('Bookings seeded successfully!');
        $this->command->info('15 bookings created with various statuses.');
    }
}
