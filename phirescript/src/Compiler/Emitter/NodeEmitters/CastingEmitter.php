<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\PrimitiveCastingNode;

class CastingEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof PrimitiveCastingNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        return  '(' . $node->to . ')' . $ctx->emitter->emit($node->value, $ctx);
    }
}
