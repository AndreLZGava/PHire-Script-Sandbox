<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Email extends SuperTypes
{
    protected static function validate(mixed $value): bool
    {
        return \filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
