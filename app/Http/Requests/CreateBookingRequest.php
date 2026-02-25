<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\GameMatch;
use App\Models\Seat;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class CreateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'match_id' => ['required', 'integer', Rule::exists('game_matches', 'id')],
            'seat_ids' => ['required', 'array', 'min:1'],
            'seat_ids.*' => ['required', 'integer', Rule::exists('seats', 'id')],
            'guests_count' => ['sometimes', 'integer', 'min:1'],
            'special_requests' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Skip custom validation if basic rules already failed
            if ($validator->errors()->any()) {
                return;
            }

            // Validate match exists and is published
            $match = GameMatch::with(['branch.seatingSections.seats'])->find($this->match_id);
            
            if (!$match) {
                $validator->errors()->add('match_id', 'Match not found.');
                return;
            }

            if (!$match->is_published) {
                $validator->errors()->add('match_id', 'This match is not available for booking.');
                return;
            }

            // Validate match is upcoming
            if ($match->status !== 'upcoming') {
                $validator->errors()->add('match_id', 'Only upcoming matches can be booked.');
                return;
            }

            // Validate within booking window
            $now = now();
            if ($match->booking_opens_at && $now->lt($match->booking_opens_at)) {
                $validator->errors()->add('match_id', 'Booking for this match has not opened yet.');
                return;
            }

            if ($match->booking_closes_at && $now->gt($match->booking_closes_at)) {
                $validator->errors()->add('match_id', 'Booking for this match has closed.');
                return;
            }

            // Validate guests count matches seat count (only if explicitly provided)
            if ($this->has('guests_count') && $this->guests_count !== count($this->seat_ids)) {
                $validator->errors()->add('guests_count', 'Guests count must match the number of seats selected.');
                return;
            }

            // Validate seats availability
            $seatIds = $this->seat_ids;
            
            // Check if seats exist and belong to the match's branch
            $seats = Seat::with('section')
                ->whereIn('id', $seatIds)
                ->get();

            if ($seats->count() !== count($seatIds)) {
                $validator->errors()->add('seat_ids', 'One or more seats are invalid.');
                return;
            }

            // Verify all seats belong to the match's branch
            foreach ($seats as $seat) {
                if ($seat->section->branch_id !== $match->branch_id) {
                    $validator->errors()->add('seat_ids', 'Selected seats do not belong to the match venue.');
                    return;
                }

                if (!$seat->is_available) {
                    $validator->errors()->add('seat_ids', "Seat {$seat->label} is not available.");
                    return;
                }
            }

            // Check if seats are already booked for this match
            $bookedSeats = DB::table('booking_seats')
                ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
                ->where('bookings.match_id', $this->match_id)
                ->whereIn('bookings.status', ['pending', 'confirmed', 'checked_in'])
                ->whereIn('booking_seats.seat_id', $seatIds)
                ->pluck('booking_seats.seat_id')
                ->toArray();

            if (!empty($bookedSeats)) {
                $bookedSeatLabels = $seats->whereIn('id', $bookedSeats)->pluck('label')->join(', ');
                $validator->errors()->add('seat_ids', "The following seats are already booked for this match: {$bookedSeatLabels}");
                return;
            }

            // Check for duplicate booking (same user, same match)
            $existingBooking = Booking::where('user_id', $this->user()->id)
                ->where('match_id', $this->match_id)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->exists();

            if ($existingBooking) {
                $validator->errors()->add('match_id', 'You already have a booking for this match.');
                return;
            }

            // Check if enough seats available on match
            if ($match->seats_available < count($seatIds)) {
                $validator->errors()->add('seat_ids', 'Not enough seats available for this match.');
                return;
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'match_id.required' => 'Match is required.',
            'match_id.exists' => 'Selected match does not exist.',
            'seat_ids.required' => 'At least one seat must be selected.',
            'seat_ids.array' => 'Seat IDs must be an array.',
            'seat_ids.min' => 'At least one seat must be selected.',
            'seat_ids.*.exists' => 'One or more selected seats are invalid.',
            'guests_count.required' => 'Number of guests is required.',
            'guests_count.min' => 'At least one guest is required.',
            'special_requests.max' => 'Special requests cannot exceed 500 characters.',
        ];
    }
}
