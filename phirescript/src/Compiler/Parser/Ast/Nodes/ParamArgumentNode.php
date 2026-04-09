<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ParamArgumentNode extends Node
{
    public function __construct(
        public Token $token,
        public array $types = [],
        public ?string $name = null,
        public mixed $value = null,
        public array $resolvedTypeInfo = [],
    ) {
    }
}
