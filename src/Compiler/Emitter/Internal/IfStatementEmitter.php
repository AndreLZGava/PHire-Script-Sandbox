<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\IfNode;
use PHireScript\Helper\Debug\Debug;

class IfStatementEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof IfNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $condition = $ctx->emitter->emit($node->condition->children[0], $ctx);

        $body = $this->emitBody($node->statements->children, $ctx);

        $code = "if ($condition) {\n $body\n}";

        if (!empty($node->elseStatements)) {
            $elseBody = $this->emitBody($node->elseStatements, $ctx);
            $code .= " else {\n            $elseBody\n        }";
        }

        return $code;
    }

    private function emitBody(array $nodes, EmitContext $ctx): string
    {
        return \implode("\n", \array_map(
            fn($n) => $ctx->emitter->emit($n, $ctx),
            $nodes
        ));
    }
}
