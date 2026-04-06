<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use Exception;
use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrowFunctionNode;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseParams;

class ArrowFunctionEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ArrowFunctionNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $indent = '';

        $signature = ' ';
        $signature .= 'function ' ;

        // --------------------
        // params
        // --------------------

        $signature .= $ctx->emitter->emit($node->parameters, $ctx);

        $code = "{$indent}{$signature}";
        $code .= $ctx->emitter->emit($node->bodyCode, $ctx);
        return $code;
    }
}
