<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types;

class UnionType
{
    public static function cast(mixed $value, array $types): mixed
    {
        foreach ($types as $typeClass) {
            try {
                return $typeClass::cast($value);
            } catch (\TypeError) {
                continue;
            }
        }

        $typeNames = array_map(fn ($c) => (new \ReflectionClass($c))->getShortName(), $types);
        throw new \TypeError("Value is not valid for any of the types: " . \implode('|', $typeNames));
    }
}
