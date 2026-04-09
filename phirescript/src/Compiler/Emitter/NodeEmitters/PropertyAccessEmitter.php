<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyAccessNode;

class PropertyAccessEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof PropertyAccessNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $object = $ctx->emitter->emit($node->object, $ctx);
        return $object . '->' . $node->property;
    }
}
