<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_holder' => ['sometimes', 'string', 'max:255'],
            'expiry_month' => ['sometimes', 'string', 'size:2'],
            'expiry_year' => ['sometimes', 'string', 'size:4'],
            'expires_at' => ['sometimes', 'date_format:Y-m', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.date_format' => 'Expiry date must be in YYYY-MM format',
            'expires_at.after' => 'Card has expired',
        ];
    }
}
