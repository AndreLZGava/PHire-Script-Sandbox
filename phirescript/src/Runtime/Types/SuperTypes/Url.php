<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Url extends SuperTypes
{
    protected static function validate(mixed $value): bool
    {
        $result = \filter_var($value, FILTER_VALIDATE_URL);

        if ($result === false) {
            return false;
        }

        $parts = parse_url($result);
        if (empty($parts['host'])) {
            return false;
        }

        return true;
    }

    protected static function transform(mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = \trim((string)$value);
        return parent::transform($value);
    }
}
