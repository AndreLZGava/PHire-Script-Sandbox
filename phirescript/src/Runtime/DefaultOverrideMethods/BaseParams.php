<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods;

class BaseParams
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $required = false,
        public mixed $defaultValue = null,
        public bool $relatedKeyParam = false,
        public array $allowedOptions = [],
    ) {
    }
}
