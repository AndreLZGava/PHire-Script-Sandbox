<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode;
use PHireScript\Compiler\Parser\Ast\Nodes\CommentNode;
use PHireScript\Helper\Debug\Debug;

class ArrayLiteralEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ArrayLiteralNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $items = [];
        foreach ($node->elements ?? [] as $n => $el) {
            if (!$el instanceof CommentNode) {
                $items[] = $ctx->emitter->emit($el, $ctx);
            }
        }

        return "[\n" . \implode(", \n", $items) . "\n]";
    }
}
