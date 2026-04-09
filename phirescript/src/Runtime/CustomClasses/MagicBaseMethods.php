<?php

declare(strict_types=1);

namespace PHireScript\Runtime\CustomClasses;

class MagicBaseMethods
{
    public function __construct(
        public string $name,
        public string $related,
        public array $return,
        public array $params = [],
    ) {
    }
}
