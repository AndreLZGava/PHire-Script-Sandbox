<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class AssignmentNode extends Statement
{
    public function __construct(
        Token $token,
        public Node $left,
        public ?Node $right = null
    ) {
    }

    public function addChild(Node $node): void
    {
        $this->right = $node;
    }
}
