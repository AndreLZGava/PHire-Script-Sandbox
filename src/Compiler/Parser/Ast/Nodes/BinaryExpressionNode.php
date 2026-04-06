<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

class BinaryExpressionNode extends Expression
{
    public function __construct(
        public $left,
        public $operator,
        public $right
    ) {
    }
}
