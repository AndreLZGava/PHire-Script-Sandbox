<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\Internal\ConstructorEmitter;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassBodyNode;

class ClassBodyEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ClassBodyNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = "{\n";
        if ($node->type !== 'class') {
            $code .= (new ConstructorEmitter())->emit($node, $ctx);
        }
        foreach ($node->children as $member) {
            if ($member) {
                $code .= $ctx->emitter->emit($member, $ctx);
            }
        }
        return $code . "}\n";
    }
}
