<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\SuperTypeNode;

class SuperTypeEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof SuperTypeNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $ctx->uses->add("PHireScript\Runtime\Types\SuperTypes\\{$node->type}");
        $value = \is_null($node->value) ? $node->value : $ctx->emitter->emit($node->value, $ctx);
        return "{$node->type}::cast({$value})";
    }
}
