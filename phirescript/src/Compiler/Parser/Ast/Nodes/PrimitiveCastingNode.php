<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class PrimitiveCastingNode extends Expression
{
    public function __construct(
        public Token $token,
        public string $to,
        public mixed $value = null,
    ) {
    }
}
