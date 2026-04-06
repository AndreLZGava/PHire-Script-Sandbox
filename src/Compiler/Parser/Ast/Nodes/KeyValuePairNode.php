<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class KeyValuePairNode extends Node
{
    public function __construct(
        Token $token,
        public ?Node $key,
        public ?Node $value
    ) {
    }
}
