<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class CardNumber extends SuperTypes
{
    protected static function transform(mixed $value): mixed
    {
        return \is_string($value) ? preg_replace('/\D/', '', $value) : $value;
    }

    protected static function validate(mixed $preparedValue): bool
    {
        if (!\is_string($preparedValue)) {
            return false;
        }

        if (!preg_match('/^\d{13,19}$/', $preparedValue)) {
            return false;
        }

        return self::luhnCheck($preparedValue);
    }

    private static function luhnCheck(string $number): bool
    {
        $sum = 0;
        $numDigits = strlen($number);
        $parity = $numDigits % 2;

        for ($i = 0; $i < $numDigits; $i++) {
            $digit = (int)$number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return ($sum % 10 === 0);
    }
}
