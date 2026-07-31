<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:credit_card,debit_card,wallet,bank_transfer'],
            'card_number' => ['required_if:type,credit_card,debit_card', 'nullable', 'string', 'min:13', 'max:19', 'regex:/^\d+$/'],
            'card_last_four' => ['nullable', 'string', 'size:4', 'regex:/^\d{4}$/'],
            'card_holder' => ['required_if:type,credit_card,debit_card', 'nullable', 'string', 'max:255', 'regex:/^[\p{L}\s]+$/u'],
            'expiry_month' => ['nullable', 'string', 'size:2', 'regex:/^(0[1-9]|1[0-2])$/'],
            'expiry_year' => ['nullable', 'string', 'size:4', 'regex:/^20\d{2}$/'],
            'expires_at' => [
                'nullable', 
                'date_format:Y-m',
                function ($attribute, $value, $fail) {
                    try {
                        $expiryDate = \Carbon\Carbon::createFromFormat('Y-m', $value)->endOfMonth();
                        if ($expiryDate->isPast()) {
                            $fail(__('Card has expired.'));
                        }
                    } catch (\Exception $e) {
                        $fail(__('Invalid expiry date.'));
                    }
                }
            ],
            'cvv' => ['nullable', 'string', 'min:3', 'max:4'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get data to be validated from the request.
     */
    public function validationData(): array
    {
        $data = parent::validationData();

        // Derive card_last_four from card_number if provided
        if (!empty($data['card_number']) && empty($data['card_last_four'])) {
            $data['card_last_four'] = substr($data['card_number'], -4);
        }

        // Build expires_at from expiry_month/expiry_year if provided
        if (!empty($data['expiry_month']) && !empty($data['expiry_year'])) {
            $data['expires_at'] = $data['expiry_year'] . '-' . $data['expiry_month'];
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'type.in' => __('Payment type must be one of: credit_card, debit_card, wallet, bank_transfer'),
            'card_last_four.size' => __('Card last four digits must be exactly 4 digits'),
            'card_last_four.regex' => __('Card last four digits must contain only numbers'),
            'expires_at.date_format' => __('Expiry date must be in YYYY-MM format'),
            'expires_at.after' => __('Card has expired.'),
            'card_holder.regex' => __('The card holder name must contain only letters and spaces.'),
            'expiry_month.regex' => __('The expiry month must be between 01 and 12.'),
            'expiry_year.regex' => __('The expiry year must be a valid year starting with 20.'),
            'card_number.min' => __('The card number must be at least 13 digits.'),
            'card_number.max' => __('The card number must not exceed 19 digits.'),
            'card_number.regex' => __('The card number must contain only digits.'),
        ];
    }
}
