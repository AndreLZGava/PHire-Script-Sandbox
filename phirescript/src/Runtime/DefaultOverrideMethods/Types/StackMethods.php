<?php

declare(strict_types=1);

namespace PHireScript\Runtime\DefaultOverrideMethods\Types;

use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;

class StackMethods extends GeneralType
{
    public function __construct(
        public array $types,
    ) {
    }

    public function push(...$params)
    {
        return new BaseMethods(
            'push!',
            '\array_push(@self, @params)',
            [],
            $this->types,
            $params
        );
    }

    public function pop()
    {
        return new BaseMethods(
            'pop!',
            '\\array_key_exists(@self)',
            ['mixed'],
        );
    }

    public function peek()
    {
        return new BaseMethods(
            'peek',
            '\end(@self)',
            ['mixed'],
        );
    }
}
