<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Uuid extends SuperTypes
{
    protected static function transform(mixed $value): mixed
    {
        if (empty($value)) {
            return self::generate();
        }
        return \is_string($value) ? strtolower(\trim($value)) : $value;
    }

    protected static function validate(mixed $preparedValue): bool
    {
        if (!\is_string($preparedValue)) {
            return false;
        }

        $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return preg_match($regex, $preparedValue) === 1;
    }

    private static function generate(): string
    {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
