<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\Internal\ConstructorEmitter;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Compiler\Parser\Ast\Nodes\TraitDefinition;
use PHireScript\Helper\Debug\Debug;

class TraitEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof TraitDefinition;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = "trait {$node->name} {\n";
        // ---- properties
        foreach ($node->body as $member) {
            if ($member instanceof PropertyNode) {
                $code .= $ctx->emitter->emit($member, $ctx) ;
            }
        }

        // ---- methods
        foreach ($node->body as $member) {
            if ($member instanceof MethodDeclarationNode) {
                $code .= $ctx->emitter->emit($member, $ctx);
            }
        }

        return $code . "}\n";
    }
}
