<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_or_phone' => ['required_without_all:email,phone', 'string'],
            'email' => ['required_without_all:email_or_phone,phone', 'string'],
            'phone' => ['required_without_all:email_or_phone,email', 'string'],
            'password' => ['required', 'string'],
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
            'email_or_phone.required' => 'Email or phone number is required',
            'password.required' => 'Password is required',
        ];
    }
}
