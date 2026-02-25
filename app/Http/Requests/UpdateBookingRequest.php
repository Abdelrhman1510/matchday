<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'guests_count' => ['sometimes', 'integer', 'min:1'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $booking = $this->route('id') ? \App\Models\Booking::find($this->route('id')) : null;

            if (!$booking) {
                return;
            }

            // Only allow updates for pending or confirmed bookings
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                $validator->errors()->add('status', 'Only pending or confirmed bookings can be updated.');
                return;
            }

            // If updating guests_count, ensure it matches the number of booked seats
            if ($this->has('guests_count')) {
                $seatsCount = $booking->seats()->count();
                if ($this->guests_count !== $seatsCount) {
                    $validator->errors()->add('guests_count', "Guests count must match the number of booked seats ({$seatsCount}).");
                    return;
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'guests_count.min' => 'At least one guest is required.',
            'special_requests.max' => 'Special requests cannot exceed 500 characters.',
        ];
    }
}
