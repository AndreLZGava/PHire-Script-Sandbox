<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class IssetOperatorNode extends Statement
{
    public function __construct(
        Token $token,
        public mixed $target
    ) {
    }
}
