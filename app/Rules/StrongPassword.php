<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use App\Models\PasswordHistory;

/**
 * Strong Password Validation Rule
 *
 * Validates passwords against configurable security requirements:
 * - Minimum length (default: 12 characters)
 * - Uppercase letters (optional, default: required)
 * - Lowercase letters (optional, default: required)
 * - Numbers (optional, default: required)
 * - Special characters (optional, default: required)
 * - Password history check (prevents reuse)
 * - Common password check (optional)
 *
 * All settings can be configured via config/password.php or environment variables.
 */
class StrongPassword implements ValidationRule
{
    /**
     * User ID for password history check (optional)
     */
    protected ?int $userId = null;

    /**
     * Create a new rule instance.
     *
     * @param int|null $userId User ID for password history check
     */
    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip validation if value is empty (for nullable fields)
        if (empty($value)) {
            return;
        }

        $errors = [];

        // Get configuration
        $minLength = config('password.min_length', 12);
        $requireUppercase = config('password.require_uppercase', true);
        $requireLowercase = config('password.require_lowercase', true);
        $requireNumber = config('password.require_number', true);
        $requireSpecial = config('password.require_special', true);
        $specialChars = config('password.special_characters', '!@#$%^&*()_+-=[]{};\':"|,.<>/?~`');
        $checkHistory = config('password.history_count', 5) > 0;
        $checkCommon = config('password.check_common_passwords', true);

        // Check minimum length
        if (strlen($value) < $minLength) {
            $errors[] = "at least {$minLength} characters";
        }

        // Check for uppercase letter
        if ($requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $errors[] = 'one uppercase letter';
        }

        // Check for lowercase letter
        if ($requireLowercase && !preg_match('/[a-z]/', $value)) {
            $errors[] = 'one lowercase letter';
        }

        // Check for number
        if ($requireNumber && !preg_match('/[0-9]/', $value)) {
            $errors[] = 'one number';
        }

        // Check for special character
        if ($requireSpecial) {
            $escapedChars = preg_quote($specialChars, '/');
            if (!preg_match('/[' . $escapedChars . ']/', $value)) {
                $displayChars = substr($specialChars, 0, 15);
                $errors[] = "one special character ({$displayChars}...)";
            }
        }

        // Check password history (if user is authenticated or ID provided)
        if ($checkHistory) {
            $userId = $this->userId ?? Auth::id();
            if ($userId && class_exists(PasswordHistory::class)) {
                try {
                    if (PasswordHistory::wasRecentlyUsed($userId, $value)) {
                        $historyCount = config('password.history_count', 5);
                        $fail("You cannot reuse any of your last {$historyCount} passwords.");
                        return;
                    }
                } catch (\Exception $e) {
                    // Password history table may not exist yet during initial setup
                    // Silently continue without history check
                }
            }
        }

        // Check common passwords (optional)
        if ($checkCommon && $this->isCommonPassword($value)) {
            $fail('This password is too common. Please choose a more unique password.');
            return;
        }

        // Build error message
        if (!empty($errors)) {
            $fail('The :attribute must contain ' . $this->formatErrors($errors) . '.');
        }
    }

    /**
     * Check if password is in common passwords list.
     *
     * @param string $password
     * @return bool
     */
    protected function isCommonPassword(string $password): bool
    {
        // Common weak passwords that should never be used
        $commonPasswords = [
            'password', 'password123', 'password1234', 'password12345',
            '123456', '1234567890', '123456789', '12345678',
            'qwerty', 'qwerty123', 'qwertyuiop',
            'admin', 'admin123', 'admin1234', 'administrator',
            'welcome', 'welcome1', 'welcome123',
            'letmein', 'iloveyou', 'sunshine', 'princess',
            'football', 'baseball', 'dragon', 'master',
            'monkey', 'shadow', 'michael', 'jennifer',
            'trustno1', 'abc123', 'passw0rd', 'Pa$$w0rd',
            // Pakistani-specific patterns
            'pakistan', 'pakistan123', 'pakistan1',
            'karachi', 'lahore', 'islamabad',
        ];

        // Check case-insensitive match
        $lowerPassword = strtolower($password);
        foreach ($commonPasswords as $common) {
            if ($lowerPassword === strtolower($common)) {
                return true;
            }
        }

        // Check for keyboard patterns
        $keyboardPatterns = [
            'qwertyuiop', 'asdfghjkl', 'zxcvbnm',
            '1234567890', '0987654321',
        ];
        foreach ($keyboardPatterns as $pattern) {
            if (stripos($lowerPassword, $pattern) !== false) {
                return true;
            }
        }

        // Check for repeated characters (e.g., "aaaaaa")
        if (preg_match('/(.)\1{4,}/', $password)) {
            return true;
        }

        // Check for sequential characters (e.g., "abcdef")
        if ($this->hasSequentialChars($password, 5)) {
            return true;
        }

        return false;
    }

    /**
     * Check if password has sequential characters.
     *
     * @param string $password
     * @param int $length Minimum sequential length to detect
     * @return bool
     */
    protected function hasSequentialChars(string $password, int $length): bool
    {
        $lowerPassword = strtolower($password);
        $len = strlen($lowerPassword);

        if ($len < $length) {
            return false;
        }

        for ($i = 0; $i <= $len - $length; $i++) {
            $sequential = true;
            for ($j = 0; $j < $length - 1; $j++) {
                $current = ord($lowerPassword[$i + $j]);
                $next = ord($lowerPassword[$i + $j + 1]);

                // Check ascending sequence
                if ($next !== $current + 1) {
                    $sequential = false;
                    break;
                }
            }
            if ($sequential) {
                return true;
            }

            // Check descending sequence
            $sequential = true;
            for ($j = 0; $j < $length - 1; $j++) {
                $current = ord($lowerPassword[$i + $j]);
                $next = ord($lowerPassword[$i + $j + 1]);

                if ($next !== $current - 1) {
                    $sequential = false;
                    break;
                }
            }
            if ($sequential) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format error messages into a readable string.
     *
     * @param array $errors
     * @return string
     */
    protected function formatErrors(array $errors): string
    {
        if (count($errors) === 1) {
            return $errors[0];
        }

        $last = array_pop($errors);
        return implode(', ', $errors) . ' and ' . $last;
    }

    /**
     * Static factory for use in validation without instantiation.
     *
     * @param int|null $userId
     * @return self
     */
    public static function forUser(?int $userId = null): self
    {
        return new self($userId);
    }
}
