<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class NullNode extends Expression implements Type
{
    private string $raw = 'Null';
    public function __construct(
        public Token $token,
    ) {
    }

    public function getRawType(): string
    {
        return $this->raw;
    }
}
