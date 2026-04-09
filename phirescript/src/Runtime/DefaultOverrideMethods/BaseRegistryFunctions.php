<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods;

use Closure;

class BaseRegistryFunctions
{
    public function __construct(
        public string $className,
        public string $name,
        public ?Closure $function = null,
        public ?string $parentClass = null,
    ) {
    }
}
