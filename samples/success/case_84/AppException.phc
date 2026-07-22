<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_84;


class AppException extends \Exception
{
    public function __construct(
        public readonly string $reason,
        public readonly int $code,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }
}

