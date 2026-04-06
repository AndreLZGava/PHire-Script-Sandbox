<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableDeclarationNode;
use PHireScript\Helper\Debug\Debug;

class VariableDeclarationEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof VariableDeclarationNode ;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $name = '$' . $node->name;
        return "{$name}";
    }
}
