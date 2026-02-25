<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPlayerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:100'],
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

            // Check if booking has reached maximum players (equal to booked seats)
            $maxPlayers = $booking->seats()->count();
            $currentPlayers = $booking->players()->count();

            if ($currentPlayers >= $maxPlayers) {
                $validator->errors()->add('name', "Maximum number of players ({$maxPlayers}) already added for this booking.");
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
            'name.required' => 'Player name is required.',
            'name.max' => 'Player name cannot exceed 255 characters.',
            'position.required' => 'Player position is required.',
            'position.max' => 'Player position cannot exceed 100 characters.',
        ];
    }
}
