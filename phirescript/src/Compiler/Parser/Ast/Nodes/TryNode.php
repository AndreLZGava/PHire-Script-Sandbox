<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class TryNode extends Statement
{
    public function __construct(
        public Token $token,
        public ?TryScopeNode $try = null,
        public array $handles = [],
        public ?AlwaysNode $always = null
    ) {
    }
}
