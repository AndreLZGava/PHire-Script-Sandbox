<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Exceptions;

use RuntimeException;

class CompileException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $line,
        public ?int $column,
    ) {
        parent::__construct($message);
    }
}
