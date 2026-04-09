<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class ArrayLiteralNode extends Expression implements Type {
    private string $raw = 'Array';
    public readonly array $keys;
    public function __construct(
        public Token $token,
        public array $elements = [],
        public array $types = [],
    ) {
        $this->keys = ['Int', 'String'];
    }

    public function getRawType(): string {
        return $this->raw;
    }
}
