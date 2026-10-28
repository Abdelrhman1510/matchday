<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            // Validate booking belongs to user
            $booking = Booking::find($this->booking_id);
            if ($booking && $booking->user_id !== $user->id) {
                $validator->errors()->add('booking_id', 'This booking does not belong to you.');
                return;
            }

            // Bookings are created as 'confirmed' (BookingService) while their payment is
            // still pending, so both statuses are payable; the paid check below blocks repeats.
            if ($booking && !in_array($booking->status, ['pending', 'confirmed'], true)) {
                $validator->errors()->add('booking_id', 'Only pending bookings can be paid.');
                return;
            }

            // Validate payment method belongs to user
            $paymentMethod = PaymentMethod::find($this->payment_method_id);
            if ($paymentMethod && $paymentMethod->user_id !== $user->id) {
                $validator->errors()->add('payment_method_id', 'This payment method does not belong to you.');
                return;
            }

            // Check if payment already paid
            // PaymentService records a successful booking charge as 'completed'.
            if ($booking && $booking->payment && in_array($booking->payment->status, ['paid', 'completed'], true)) {
                $validator->errors()->add('booking_id', 'This booking has already been paid.');
            }
        });
    }
}
