<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Helper\Debug\Debug;

class PropertyEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof PropertyNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $visibility = isset($node->modifiers[0]) &&
            $node->modifiers[0] === 'abstract' ?
            'public ' :
            $node->modifiers[0] . ' ';
        $type = $ctx->types->phpType($node);
        $name = '$' . $node->name;
        $defaultValue = $node->value ? ' = ' . $node->value : '';

        return "    {$visibility}{$type} {$name}{$defaultValue};\n";
    }
}
