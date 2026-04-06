<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class RangeNode extends Expression
{
    public int $left;
    public int $right;
    public function __construct(
        public Token $token,
    ) {
        $numbers = \explode('..', $this->token->value);
        $this->left = (int) $numbers[0];
        $this->right = (int) $numbers[1];
    }
}
