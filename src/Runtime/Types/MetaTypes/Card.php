<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\MetaTypes;

use PHireScript\Runtime\Types\MetaTypes;
use PHireScript\Runtime\Types\SuperTypes\CardNumber;
use PHireScript\Runtime\Types\SuperTypes\Cvv;
use PHireScript\Runtime\Types\SuperTypes\ExpiryDate;

class Card extends MetaTypes
{
    public CardNumber $number;
    public Cvv $cvv;
    public string $holderName;
    public string $brand;
    public ExpiryDate $expiry;

    public function __construct(
        mixed $number = null,
        mixed $cvv = null,
        string $holder_name = '',
        mixed $expiry = null,
        array $data = [],
    ) {
        $source = !empty($data) ? $data : [
            'number' => $number,
            'cvv' => $cvv,
            'holder_name' => $holder_name,
            'expiry' => $expiry
        ];

        $this->number = CardNumber::cast($source['number']);
        $this->cvv = Cvv::cast($source['cvv']);
        $this->holderName = strtoupper(\trim((string) $source['holder_name']));
        $this->brand = $this->detectBrand((string) $this->number);
        $this->expiry = ExpiryDate::cast($source['expiry']);
    }

    protected function detectBrand(string $number): string
    {
        $n = preg_replace('/\D/', '', $number);
        return match (true) {
            str_starts_with((string) $n, '4') => 'Visa',
            preg_match('/^5[1-5]/', (string) $n) => 'Mastercard',
            str_starts_with((string) $n, '34') || str_starts_with((string) $n, '37') => 'Amex',
            default => 'Unknown'
        };
    }

    public function isExpired(): bool
    {
        return ExpiryDate::isPast((string) $this->expiry);
    }

    protected static function transform(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    protected static function validate(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $required = ['number', 'cvv', 'holder_name', 'expiry'];
        foreach ($required as $field) {
            if (!isset($value[$field]) || empty($value[$field])) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        $lastFour = substr((string) $this->number, -4);
        return sprintf(
            "%s **** %s (Exp: %s)",
            $this->brand,
            $lastFour,
            ExpiryDate::format((string) $this->expiry)
        );
    }
}
