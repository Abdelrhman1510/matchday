<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * BUG-091: matches created without an explicit capacity defaulted to
     * seats_available = 0, which makes can_book always false — users can never book
     * them. Backfill those matches (that have no bookings yet, so they were
     * never usable rather than genuinely sold out) with the branch's real seat
     * count.
     */
    public function up(): void
    {
        $matches = DB::table('game_matches')
            ->where('seats_available', '<=', 0)
            ->get(['id', 'branch_id']);

        foreach ($matches as $match) {
            // Leave anything with existing bookings alone — 0 there could be a
            // real sellout, not the creation default.
            $hasBookings = DB::table('bookings')->where('match_id', $match->id)->exists();
            if ($hasBookings) {
                continue;
            }

            $seatCount = DB::table('seats')
                ->join('seating_sections', 'seats.section_id', '=', 'seating_sections.id')
                ->where('seating_sections.branch_id', $match->branch_id)
                ->where('seats.is_available', true)
                ->count();

            if ($seatCount <= 0) {
                $seatCount = (int) DB::table('branches')->where('id', $match->branch_id)->value('total_seats');
            }

            if ($seatCount > 0) {
                DB::table('game_matches')
                    ->where('id', $match->id)
                    ->update(['seats_available' => $seatCount]);
            }
        }
    }

    /**
     * Irreversible data backfill — the original zeros carried no information,
     * so there is nothing meaningful to restore.
     */
    public function down(): void
    {
        // no-op
    }
};
