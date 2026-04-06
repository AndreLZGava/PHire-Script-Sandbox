<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Json extends SuperTypes
{
    protected static function transform(mixed $value): mixed
    {
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : (array) $value;
        }

        return (array) $value;
    }

    protected static function validate(mixed $preparedValue): bool
    {
        return is_array($preparedValue) || is_object($preparedValue);
    }
}
