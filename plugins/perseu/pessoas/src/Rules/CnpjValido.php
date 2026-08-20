<?php

namespace Perseu\Pessoas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjValido implements ValidationRule
{
    private const FIRST_DIGIT_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const SECOND_DIGIT_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail(__('pessoas::validation.rules.cnpj'));
        }
    }

    public static function isValid(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        if (self::checkDigit($cnpj, self::FIRST_DIGIT_WEIGHTS) !== (int) $cnpj[12]) {
            return false;
        }

        if (self::checkDigit($cnpj, self::SECOND_DIGIT_WEIGHTS) !== (int) $cnpj[13]) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int>  $weights
     */
    private static function checkDigit(string $cnpj, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $i => $weight) {
            $sum += ((int) $cnpj[$i]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
