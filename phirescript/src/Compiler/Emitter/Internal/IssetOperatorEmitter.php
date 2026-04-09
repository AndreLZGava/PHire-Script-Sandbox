<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\IssetOperatorNode;

class IssetOperatorEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof IssetOperatorNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        return 'isset(' . $ctx->emitter->emit($node->target, $ctx) . ')';
    }
}
