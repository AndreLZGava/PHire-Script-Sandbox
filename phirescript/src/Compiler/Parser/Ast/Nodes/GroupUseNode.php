<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class GroupUseNode extends Statement
{
    public function __construct(
        public Token $token,
        public array $parts = [],
    ) {
    }
}
