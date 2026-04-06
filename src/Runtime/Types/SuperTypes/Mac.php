<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types\SuperTypes;

use PHireScript\Runtime\Types\SuperTypes;

class Mac extends SuperTypes
{
    protected static function validate(mixed $value): bool
    {
        return \filter_var($value, FILTER_VALIDATE_MAC) !== false;
    }
}
