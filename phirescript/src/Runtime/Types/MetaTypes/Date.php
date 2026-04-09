<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use PHireScript\Runtime\Types\MetaTypes;

class Date extends MetaTypes
{
    protected \DateTimeImmutable $date;

    protected static array $formatAliases = [
    'iso'      => 'Y-m-d',
    'br'       => 'd/m/Y',
    'human'    => 'j \d\e F \d\e Y',
    ];

    public function __construct(mixed $value = 'now')
    {
        $transformed = self::transform($value);

        if (!self::validate($transformed)) {
            throw new \TypeError("Date format invalid!");
        }

        $this->date = $transformed->setTime(0, 0, 0);
    }

    protected static function transform(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        try {
            if (is_numeric($value)) {
                return (new \DateTimeImmutable())->setTimestamp((int)$value);
            }

            if (\is_string($value)) {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                    return \DateTimeImmutable::createFromFormat('d/m/Y', $value);
                }
                return new \DateTimeImmutable($value);
            }
        } catch (\Exception) {
            return false;
        }

        return false;
    }

    protected static function validate(mixed $value): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'year'  => (int)$this->date->format('Y'),
            'month' => (int)$this->date->format('m'),
            'day'   => (int)$this->date->format('d'),
            'timestamp' => $this->date->getTimestamp(),
            default => null
        };
    }

    public function format(string $style = 'iso'): string
    {
        $mask = self::$formatAliases[$style] ?? $style;
        return $this->date->format($mask);
    }

    public function __toString(): string
    {
        return $this->format('iso');
    }
}
