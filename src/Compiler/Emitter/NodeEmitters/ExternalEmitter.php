<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ExternalNode;

class ExternalEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ExternalNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = '';
        foreach ($node->namespaces as $namespace) {
            $code .= "use {$namespace->namespace}";
            if ($namespace->alias) {
                $code .= " as {$namespace->alias}";
            }
            $code .= ";\n";
        }
        return $code;
    }
}
