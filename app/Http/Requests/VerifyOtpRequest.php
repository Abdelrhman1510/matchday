<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize inputs before validation so they match exactly what the server stored:
     * - Email: lowercase + trim (cache keys are case-sensitive).
     * - OTP: strip non-digits + trim whitespace (BUG-051 / BUG-005 — Android keyboards,
     *   especially on Samsung Galaxy with Arabic/RTL locale, inject invisible Unicode
     *   marks or surrounding spaces that inflate the raw length past 6 chars, making
     *   the size:6 rule reject a correct OTP before the service ever sees it).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }

        if ($this->has('otp')) {
            $this->merge(['otp' => preg_replace('/\D/', '', trim((string) $this->input('otp')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
            'type' => ['nullable', 'string', 'in:register,password_reset'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'otp.required' => 'The verification code is required.',
            'otp.size' => 'The verification code must be 6 digits.',
        ];
    }
}
