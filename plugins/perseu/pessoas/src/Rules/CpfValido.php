<?php

namespace Perseu\Pessoas\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid((string) $value)) {
            $fail(__('pessoas::validation.rules.cpf'));
        }
    }

    public static function isValid(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position <= 10; $position++) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) {
                $sum += ((int) $cpf[$i]) * (($position + 1) - $i);
            }

            $expectedDigit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$position] !== $expectedDigit) {
                return false;
            }
        }

        return true;
    }
}
