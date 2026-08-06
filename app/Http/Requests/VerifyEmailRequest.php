<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the OTP before validation (BUG-051 / BUG-005):
     * Strip non-digit characters and trim whitespace so Android keyboards that
     * inject invisible RTL marks or spaces don't cause size:6 to reject a correct code.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('otp')) {
            $this->merge(['otp' => preg_replace('/\D/', '', trim((string) $this->input('otp')))]);
        }
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'OTP is required.',
            'otp.size' => 'OTP must be exactly 6 digits.',
        ];
    }
}
