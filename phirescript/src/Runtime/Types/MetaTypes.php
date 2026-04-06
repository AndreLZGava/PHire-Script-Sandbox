<?php

declare(strict_types=1);

namespace PHireScript\Runtime\Types;

abstract class MetaTypes implements \Stringable
{
    protected mixed $innerValue;

    public function __construct(mixed $value)
    {
        $this->innerValue = static::transform($value);

        if (!static::validate($this->innerValue)) {
            throw new \TypeError("Couldn't instantiate MetaType " . static::class);
        }
    }

    abstract protected static function transform(mixed $value): mixed;
    abstract protected static function validate(mixed $value): bool;

    abstract public function __toString(): string;
}
