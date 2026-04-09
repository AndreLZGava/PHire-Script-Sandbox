<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Duration extends SuperTypes
{
    protected static function transform(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        if (!\is_string($value)) {
            return $value;
        }

        $value = strtolower(\trim($value));
        $totalSeconds = 0;

        preg_match_all('/(\d+)\s*(h|m|s)/', $value, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $value;
        }

        foreach ($matches as $match) {
            $num = (int)$match[1];
            $unit = $match[2];

            switch ($unit) {
                case 'h':
                    $totalSeconds += $num * 3600;
                    break;
                case 'm':
                    $totalSeconds += $num * 60;
                    break;
                case 's':
                    $totalSeconds += $num;
                    break;
            }
        }

        return $totalSeconds;
    }

    protected static function validate(mixed $preparedValue): bool
    {
        return \is_int($preparedValue) && $preparedValue >= 0;
    }
}
