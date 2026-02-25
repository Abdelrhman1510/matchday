<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB in kilobytes
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'The avatar must not be greater than 5MB.',
        ];
    }
}
