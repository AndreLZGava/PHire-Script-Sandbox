<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class ObjectLiteralNode extends Expression implements Type
{

    public function __construct(
        public Token $token,
        public array|ArrayLiteralNode $properties = []
    ) {
    }

    public function getRawType(): string
    {
        return 'Object';
    }
}
