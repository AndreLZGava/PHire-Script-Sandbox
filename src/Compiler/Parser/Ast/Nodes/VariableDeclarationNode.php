<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class VariableDeclarationNode extends Statement
{
    public function __construct(
        Token $token,
        public string $name,
        public ?Node $value = null,
        public ?Node $type = null,
        public bool $isConst = false
    ) {
    }
}
