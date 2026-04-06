<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Helper\Debug\Debug;

class VariableReferenceNode extends Statement implements Type
{
    public function __construct(
        public Token $token,
        public string $name,
        public ?Node $type,
        public VariableDeclarationNode $value,
        public bool $isConst = false
    ) {
    }

    public function getRawType(): string
    {
        if ($this->type instanceof PrimitiveCastingNode) {
            return $this->type->to;
        }
        return $this->type->getRawType();
    }
}
