<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class HandleNode extends Statement
{
    public function __construct(
        public Token $token,
        public ?ParamsListNode $param = null,
        public array $children = [],
    ) {
    }
}
