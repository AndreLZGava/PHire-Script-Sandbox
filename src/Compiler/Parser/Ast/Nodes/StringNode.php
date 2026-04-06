<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class StringNode extends Expression implements Type
{
    private string $raw = 'String';
    public function __construct(
        public Token $token,
        public string $value,
    ) {
    }

    public function getRawType(): string
    {
        return $this->raw;
    }
}
