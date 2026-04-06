<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\HandleNode;

class HandleEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof HandleNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = "catch";
        if ($node->param) {
            $code .= $ctx->emitter->emit($node->param, $ctx);
        }
        $code .= "{\n";
        foreach ($node->children[0]->children ?? [] as $child) {
            $code .= $ctx->emitter->emit($child, $ctx);
            $code .= "\n";
        }
        $code .= "}\n";
        return $code;
    }
}
