<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Cron extends SuperTypes
{
    private const MACROS = [
        '@YEARLY',
        '@ANNUALLY',
        '@MONTHLY',
        '@WEEKLY',
        '@DAILY',
        '@HOURLY',
        '@REBOOT',
    ];

    private const MONTHS = [
        'JAN' => 1,
        'FEB' => 2,
        'MAR' => 3,
        'APR' => 4,
        'MAY' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AUG' => 8,
        'SEP' => 9,
        'OCT' => 10,
        'NOV' => 11,
        'DEC' => 12,
    ];

    private const DAYS = [
        'SUN' => 0,
        'MON' => 1,
        'TUE' => 2,
        'WED' => 3,
        'THU' => 4,
        'FRI' => 5,
        'SAT' => 6,
    ];

    protected static function transform(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }
        return preg_replace('/\s+/', ' ', strtoupper(\trim($value)));
    }

    protected static function validate(mixed $preparedValue): bool
    {
        if (!\is_string($preparedValue)) {
            return false;
        }

        if (\in_array($preparedValue, self::MACROS, true)) {
            return true;
        }

        $parts = \explode(' ', $preparedValue);
        $count = \count($parts);

        if ($count !== 5 && $count !== 6) {
            return false;
        }

        $offset = $count === 6 ? 1 : 0;

        return ($count === 6 ? self::checkField($parts[0], 0, 59) : true) &&
            self::checkField($parts[$offset + 0], 0, 59) &&
            self::checkField($parts[$offset + 1], 0, 23) &&
            self::checkField($parts[$offset + 2], 1, 31) &&
            self::checkField($parts[$offset + 3], 1, 12, self::MONTHS) &&
            self::checkField($parts[$offset + 4], 0, 7, self::DAYS);
    }

    private static function checkField(
        string $field,
        int $min,
        int $max,
        array $namedMap = []
    ): bool {
        foreach (\explode(',', $field) as $segment) {
            if (!self::checkSegment($segment, $min, $max, $namedMap)) {
                return false;
            }
        }
        return true;
    }

    private static function checkSegment(
        string $segment,
        int $min,
        int $max,
        array $namedMap
    ): bool {
        if (\str_contains($segment, '/')) {
            [$base, $step] = \explode('/', $segment, 2);
            if (!ctype_digit($step) || (int)$step <= 0) {
                return false;
            }
            return self::checkSegment($base, $min, $max, $namedMap);
        }

        if ($segment === '*') {
            return true;
        }

        if (\str_contains($segment, '-')) {
            [$start, $end] = \explode('-', $segment, 2);
            $start = self::resolveValue($start, $namedMap);
            $end   = self::resolveValue($end, $namedMap);

            if ($start === null || $end === null) {
                return false;
            }
            if ($start > $end) {
                return false;
            }

            return self::inRange($start, $min, $max)
                && self::inRange($end, $min, $max);
        }

        $value = self::resolveValue($segment, $namedMap);
        if ($value === null) {
            return false;
        }

        return self::inRange($value, $min, $max);
    }

    private static function resolveValue(string $value, array $namedMap): ?int
    {
        if (ctype_digit($value)) {
            return (int)$value;
        }

        return $namedMap[$value] ?? null;
    }

    private static function inRange(int $value, int $min, int $max): bool
    {
        if ($value === 7 && $max === 7) {
            return true;
        }
        return $value >= $min && $value <= $max;
    }
}
