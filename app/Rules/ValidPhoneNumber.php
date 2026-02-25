<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $value);

        // Check if it's a valid format
        // Supports: +966512345678, 0512345678, 512345678, etc.
        if (!preg_match('/^\+?[0-9]{10,15}$/', $cleaned)) {
            $fail('The :attribute must be a valid phone number.');
            return;
        }

        // Saudi Arabia specific validation (if starts with +966 or 05)
        if (preg_match('/^\+?966/', $cleaned)) {
            // Remove country code
            $number = preg_replace('/^\+?966/', '', $cleaned);
            
            // Check Saudi format: should be 9 digits starting with 5
            if (!preg_match('/^5[0-9]{8}$/', $number)) {
                $fail('The :attribute must be a valid Saudi phone number (e.g., 0512345678).');
                return;
            }
        } elseif (preg_match('/^05/', $value)) {
            // Local format starting with 05
            if (!preg_match('/^05[0-9]{8}$/', $cleaned)) {
                $fail('The :attribute must be a valid Saudi phone number (e.g., 0512345678).');
                return;
            }
        }
    }
}
