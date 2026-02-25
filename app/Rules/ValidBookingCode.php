<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBookingCode implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Booking code format: BOOK-XXXXXX (6 uppercase alphanumeric characters)
        // Example: BOOK-A1B2C3, BOOK-123456
        
        if (!preg_match('/^BOOK-[A-Z0-9]{6}$/', strtoupper($value))) {
            $fail('The :attribute must be a valid booking code format (e.g., BOOK-A1B2C3).');
        }
    }
}
