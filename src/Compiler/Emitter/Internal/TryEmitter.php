<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\TryNode;

class TryEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof TryNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = "try\n";
        $code .= "{\n";
        foreach ($node->try->children as $child) {
            $code .= $ctx->emitter->emit($child, $ctx);
            $code .= "\n";
        }
        $code .= "}\n";

        foreach ($node->handles as $handle) {
            $code .= $ctx->emitter->emit($handle, $ctx);
        }

        if (isset($node->always)) {
            $code .= $ctx->emitter->emit($node->always, $ctx);
        }
        return $code;
    }
}
