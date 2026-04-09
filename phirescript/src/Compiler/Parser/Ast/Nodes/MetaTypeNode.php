<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class MetaTypeNode extends Expression
{
    public string $type;
    public function __construct(
        Token $token,
        public mixed $value,
    ) {
        $this->type = $token->value;
    }
}
