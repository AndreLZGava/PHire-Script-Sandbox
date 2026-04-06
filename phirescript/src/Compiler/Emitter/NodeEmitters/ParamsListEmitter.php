<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsListNode;

class ParamsListEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ParamsListNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = '(';
        $params = [];
        foreach ($node->params as $param) {
            $params[] = $ctx->emitter->emit($param, $ctx);
        }
        $code .= \implode(', ', $params);
        $code .= ')';
        return $code;
    }
}
