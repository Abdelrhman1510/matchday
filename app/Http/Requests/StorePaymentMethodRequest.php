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
            'card_holder' => ['required_if:type,credit_card,debit_card', 'nullable', 'string', 'max:255'],
            'expiry_month' => ['nullable', 'string', 'size:2'],
            'expiry_year' => ['nullable', 'string', 'size:4'],
            'expires_at' => ['nullable', 'date_format:Y-m'],
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
            'type.in' => 'Payment type must be one of: credit_card, debit_card, wallet, bank_transfer',
            'card_last_four.size' => 'Card last four digits must be exactly 4 digits',
            'card_last_four.regex' => 'Card last four digits must contain only numbers',
            'expires_at.date_format' => 'Expiry date must be in YYYY-MM format',
            'expires_at.after' => 'Card has expired',
        ];
    }
}
