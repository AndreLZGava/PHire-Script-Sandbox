<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Exceptions;

use RuntimeException;

class CheckerException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $line,
        public readonly ?int $column,
    ) {
        parent::__construct($message);
    }
}
