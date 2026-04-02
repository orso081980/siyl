<?php

namespace App\Service;

/**
 * Stateless password-policy validator.
 *
 * Rule: at least 6 letters · 2 digits · 2 non-alphanumeric symbols.
 * Returns a human-readable message on failure, or null when the password passes.
 */
final class PasswordPolicy
{
    public static function validate(string $password): ?string
    {
        if (preg_match_all('/[a-zA-Z]/', $password) < 6) {
            return 'Password must contain at least 6 letters.';
        }

        if (preg_match_all('/\d/', $password) < 2) {
            return 'Password must contain at least 2 numbers.';
        }

        if (preg_match_all('/[^a-zA-Z0-9]/', $password) < 2) {
            return 'Password must contain at least 2 symbols.';
        }

        return null;
    }
}
