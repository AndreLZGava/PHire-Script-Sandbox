<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types;

abstract class SuperTypes
{
    public static function cast(mixed $value = null): mixed
    {
        $preparedValue = static::transform($value);

        if (!static::validate($preparedValue)) {
            $type = (new \ReflectionClass(static::class))->getShortName();
            $value = is_scalar($value) ? $value : get_debug_type($value);
            throw new \TypeError("The value ($value) is not a valid type ($type).");
        }

        return $preparedValue;
    }

    abstract protected static function validate(mixed $preparedValue): bool;

    protected static function transform(mixed $value): mixed
    {
        return $value;
    }
}
