<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamArgumentNode;
use PHireScript\Helper\Debug\Debug;

class ParamArgumentEmitter extends NodeEmitterAbstract implements NodeEmitter {
    public function supports(object $node, EmitContext $ctx): bool {
        return $node instanceof ParamArgumentNode;
    }

    public function emit(object $node, EmitContext $ctx): string {
        $code = \implode('|', $node->types);
        $variable = " $" . $node->name;
        $value = $node->value ? ' = ' . ($node->value instanceof Node ? $node->value->value : $node->value) : '';
        $code .= $variable . $value;
        return $code;
    }
}
