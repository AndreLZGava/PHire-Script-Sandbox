<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ValidateBodyNode extends ComplexObjectDefinition
{
    public function __construct(
        public Token $token,
        public string $bodyOf,
        public string $type,
        public array $children = []
    ) {
    }
}
