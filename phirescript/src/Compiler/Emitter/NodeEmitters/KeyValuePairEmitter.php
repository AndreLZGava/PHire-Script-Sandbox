<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\KeyValuePairNode;
use PHireScript\Helper\Debug\Debug;

class KeyValuePairEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof KeyValuePairNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $key = $ctx->emitter->emit($node->key, $ctx);
        $value = $ctx->emitter->emit($node->value, $ctx);
        return "{$key} => {$value}";
    }
}
