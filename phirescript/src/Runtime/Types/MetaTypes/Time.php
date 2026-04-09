<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use PHireScript\Runtime\Types\MetaTypes;

class Time extends MetaTypes
{
    protected int $secondsSinceMidnight;

    public function __construct(mixed $value = 'now')
    {
        $transformed = self::transform($value);

        if (!self::validate($transformed)) {
            throw new \TypeError("Invalid time format.");
        }

        $this->secondsSinceMidnight = $transformed;
    }

    protected static function transform(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if ($value === 'now') {
            $now = new \DateTime();
            return ($now->format('H') * 3600) + ($now->format('i') * 60) + $now->format('s');
        }

        if (\is_string($value)) {
            if (preg_match('/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/', $value, $matches)) {
                $h = (int)$matches[1];
                $m = (int)$matches[2];
                $s = (int)($matches[3] ?? 0);
                return ($h * 3600) + ($m * 60) + $s;
            }
        }

        return false;
    }

    protected static function validate(mixed $value): bool
    {
        return \is_int($value) && $value >= 0 && $value < 86400;
    }


    public function __get(string $name)
    {
        return match ($name) {
            'hour'    => (int) floor($this->secondsSinceMidnight / 3600),
            'minute'  => (int) floor(($this->secondsSinceMidnight % 3600) / 60),
            'second'  => $this->secondsSinceMidnight % 60,
            'total_seconds' => $this->secondsSinceMidnight,
            default   => null
        };
    }

    public function format(string $style = 'short'): string
    {
        $h = str_pad($this->hour, 2, '0', STR_PAD_LEFT);
        $m = str_pad($this->minute, 2, '0', STR_PAD_LEFT);
        $s = str_pad($this->second, 2, '0', STR_PAD_LEFT);

        return match ($style) {
            'short' => "$h:$m",
            'full'  => "$h:$m:$s",
            '12h'   => date("g:i a", mktime($this->hour, $this->minute, $this->second)),
            default => "$h:$m"
        };
    }

    public function __toString(): string
    {
        return $this->format('full');
    }
}
