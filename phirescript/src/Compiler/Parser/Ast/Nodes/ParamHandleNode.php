<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ParamHandleNode extends Node
{
    public function __construct(
        public Token $token,
        public array $types = [],
        public ?string $name = null,
        public array $resolvedTypeInfo = [],
    ) {
    }
}
