<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class NamespaceNode extends Statement
{
    public function __construct(
        public Token $token,
        public string $namespace = '',
        public ?string $alias = null,
    ) {
    }
}
