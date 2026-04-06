<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\WithNode;

class WithEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof WithNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = '';
        foreach ($node->children ?? [] as $trait) {
            $code .= '    use ' . $trait . ";\n";
        }
        return $code;
    }
}
