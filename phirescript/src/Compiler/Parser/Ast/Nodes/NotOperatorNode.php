<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class NotOperatorNode extends Statement
{
    public function __construct(
        Token $token,
        public mixed $expression
    ) {
    }
}
