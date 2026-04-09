<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers\Context;

use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Managers\Context\Context;

class ContextState
{
    public function __construct(
        public Context $context,
        public Node $element,
        public ?ContextState $parent = null,
        public array $children = [],
    ) {
    }

    public function addChild(ContextState $child): void
    {
        $this->children[] = $child;
    }

    public function is(Context $context): bool
    {
        return $this->context === $context;
    }
}
