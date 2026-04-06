<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\AlwaysNode;

class AlwaysEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof AlwaysNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = "finally\n";
        $code .= "{\n";
        foreach ($node->scope->children as $child) {
            $code .= $ctx->emitter->emit($child, $ctx);
            $code .= "\n";
        }
        $code .= "}\n";
        return $code;
    }
}
