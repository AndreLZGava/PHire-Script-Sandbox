<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class LiteralNode extends Expression
{
    public function __construct(
        Token $token,
        public mixed $value,
        public string $rawType
    ) {
    }
}
