<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\UseNode;
use PHireScript\Helper\Debug\Debug;

class UseEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof UseNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = '';
        $namespaces = $ctx->dependencyManager->getNodes();
        foreach ($node->packages as $package) {
            $code .= "use {$namespaces[$package->package]->namespace}";
            if ($package->alias) {
                $code .= " as {$package->alias}";
            }
            $code .= ";\n";
        }
        return $code;
    }
}
