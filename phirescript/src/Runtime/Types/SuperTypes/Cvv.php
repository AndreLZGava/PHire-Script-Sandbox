<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Cvv extends SuperTypes
{
    protected static function transform(mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return null;
        }
        return \is_string($value) ? \trim($value) : (string) $value;
    }

    protected static function validate(mixed $preparedValue): bool
    {
        if (is_null($preparedValue)) {
            return false;
        }

        return preg_match('/^\d{3,4}$/', (string) $preparedValue) === 1;
    }
}
