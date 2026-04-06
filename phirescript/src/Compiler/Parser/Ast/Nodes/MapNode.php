<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types\Type;
use PHireScript\Compiler\Parser\Managers\Token\Token;

class MapNode extends Collection implements Type {
    private string $raw = 'Map';
    public readonly array $keys;
    public function __construct(
        public Token $token,
        public array $types = [],
    ) {
        $this->keys = ['String'];
    }

    public function getRawType(): string {
        return $this->raw;
    }
}
