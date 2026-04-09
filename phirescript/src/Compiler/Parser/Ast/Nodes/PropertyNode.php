<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class PropertyNode extends Node
{
    public function __construct(
        public Token $token,
        public array $types,
        public string $name = '',
        public ?Node $value = null,
        public array $modifiers = [],
        public array $resolvedTypeInfo = [],
    ) {
    }
}
