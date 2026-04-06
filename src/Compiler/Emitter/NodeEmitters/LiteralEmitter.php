<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\LiteralNode;
use PHireScript\Helper\Debug\Debug;

class LiteralEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof LiteralNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        return match ($node->rawType) {
            'String' => $node->value,
            'Property' => $this->clearAsString($node->value),
            default  => (string) $node->value
        };
    }

    private function clearAsString($found)
    {
        $found = \trim($found, '"');
        $found = \trim($found, "'");
        return '"' . $found . '"';
    }
}
