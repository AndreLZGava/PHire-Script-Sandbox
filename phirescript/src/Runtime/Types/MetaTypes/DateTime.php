<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use PHireScript\Runtime\Types\MetaTypes;

class DateTime extends MetaTypes
{
    public Date $date;
    public Time $time;

    public function __construct(mixed $value = 'now')
    {
        $data = self::transform($value);

        if (!self::validate($data)) {
            throw new \TypeError("Invalid date and/or time format.");
        }

        $this->date = $data['date'];
        $this->time = $data['time'];
    }

    protected static function transform(mixed $value): mixed
    {
        try {
            $dt = ($value instanceof \DateTimeInterface) ?
            $value :
            new \DateTime($value);

            return [
            'date' => new Date($dt->format('Y-m-d')),
            'time' => new Time($dt->format('H:i:s'))
            ];
        } catch (\Exception) {
            if (
                \is_string($value) &&
                preg_match(
                    '/^(\d{2}\/\d{2}\/\d{4})\s+(\d{2}:\d{2}(?::\d{2})?)$/',
                    $value,
                    $matches
                )
            ) {
                return [
                'date' => new Date($matches[1]),
                'time' => new Time($matches[2])
                ];
            }
            return false;
        }
    }

    protected static function validate(mixed $value): bool
    {
        return isset($value['date']) && $value['date'] instanceof Date &&
        isset($value['time']) && $value['time'] instanceof Time;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'date' => $this->date,
            'time' => $this->time,
            'timestamp' => $this->toPhpDateTime()->getTimestamp(),
            default => null
        };
    }

    public function toPhpDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            $this->date->format('iso') . ' ' . $this->time->format('full')
        );
    }

    public function format(string $style = 'iso'): string
    {
        return match ($style) {
            'br'    => $this->date->format('br') . ' ' . $this->time->format('short'),
            'full'  => $this->date->format('br') . ' ' . $this->time->format('full'),
            'iso'   => $this->date->format('iso') . ' ' . $this->time->format('full'),
            default => $this->toPhpDateTime()->format($style)
        };
    }

    public function __toString(): string
    {
        return $this->format('iso');
    }
}
