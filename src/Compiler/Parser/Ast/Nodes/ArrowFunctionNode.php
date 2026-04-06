<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class ArrowFunctionNode extends Node implements Type
{
    private string $raw = 'Function';
    public function __construct(
        public Token $token,
        public ?MethodScopeNode $bodyCode = null,
        public ?ParamsListNode $parameters = null,
        public ?ReturnTypeNode $returnType = null,
    ) {
    }

    public function getRawType(): string
    {
        return $this->raw;
    }
}
