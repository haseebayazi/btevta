<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Strong Password Validation Rule
 *
 * Ensures passwords meet security requirements:
 * - At least 8 characters (enforced separately)
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 * - At least one special character
 */
class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if value is empty (for nullable fields)
        if (empty($value)) {
            return;
        }

        $errors = [];

        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'one uppercase letter';
        }

        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'one lowercase letter';
        }

        // Check for number
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'one number';
        }

        // Check for special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':\"\\|,.<>\/?~`]/', $value)) {
            $errors[] = 'one special character (!@#$%^&*()_+-=[]{};\':"|,.<>/?~`)';
        }

        if (!empty($errors)) {
            $fail('The :attribute must contain at least ' . $this->formatErrors($errors) . '.');
        }
    }

    /**
     * Format error messages into a readable string.
     */
    protected function formatErrors(array $errors): string
    {
        if (count($errors) === 1) {
            return $errors[0];
        }

        $last = array_pop($errors);
        return implode(', ', $errors) . ' and ' . $last;
    }
}
