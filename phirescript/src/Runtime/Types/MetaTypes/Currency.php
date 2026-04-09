<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use NumberFormatter;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Types\MetaTypes;

class Currency extends MetaTypes
{
    protected int $amount;
    protected string $currency;
    protected string $locale;

    public function __construct(mixed $value, string $currency = 'USD', string $locale = 'en_US')
    {
        $this->locale = ($value instanceof self) ? $value->locale : $locale;
        $this->currency = ($value instanceof self) ? $value->currency : strtoupper($currency);

        if ($value instanceof self) {
            $this->amount = $value->cents;
        } else {
            $this->amount = $this->parseValue($value);
        }

        if (!self::validate($this->amount)) {
            throw new \InvalidArgumentException("Invalid currency value.");
        }
    }

    protected function parseValue(mixed $value): int
    {
        if (\is_string($value)) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);
            $number = $formatter->formatCurrency($value, $this->currency);
            // Debug::show($value, $number, $this->locale);exit;
            //$clean = preg_replace('/[^\d.,-]/', '', $value);
            $formatter = new NumberFormatter($this->locale, NumberFormatter::TYPE_DOUBLE);
            $number = $formatter->parse($number);
            Debug::show($value, $number, (int) round($number * 100));
            exit;
            return ($number === false) ? 0 : (int) round($number * 100);
        }

        return self::transform($value);
    }

    protected static function transform(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value * 100);
        }

        if (\is_string($value)) {
            $clean = \str_replace(',', '.', preg_replace('/[^\d.,-]/', '', $value));
            return (int) round((float) $clean * 100);
        }

        return 0;
    }

    protected static function validate(mixed $value): bool
    {
        return \is_int($value);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'decimal' => $this->amount / 100,
            'cents'   => $this->amount,
            'currency' => $this->currency,
            'locale'   => $this->locale,
            default => null
        };
    }

    public function convertTo(string $newCurrency, float $exchangeRate): self
    {
        $newCents = (int) round($this->amount * $exchangeRate);
        return new self($newCents, $newCurrency, $this->locale);
    }

    public function add(Currency $other): Currency
    {
        if ($this->currency !== $other->currency) {
            throw new \Exception("Not possible sum different currencies. Convert them first.");
        }
        return new self($this->amount + $other->cents, $this->currency, $this->locale);
    }

    public function format(?string $locale = null): string
    {
        $targetLocale = $locale ?? $this->locale;
        $formatter = new NumberFormatter($targetLocale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($this->decimal, $this->currency);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
