<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ReturnNode;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Types\TypeGuard;

class ReturnEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ReturnNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $expr = $node->expression
        ? $ctx->emitter->emit($node->expression, $ctx)
        : '';

        if (
            $ctx->dev &&
            \is_string($ctx->currentMethodReturnType) &&
            \str_starts_with($ctx->currentMethodReturnType, '[')
        ) {
            $inner = \trim($ctx->currentMethodReturnType, '[]');
            $types = "['" . \implode("','", \explode('|', $inner)) . "']";

            $ctx->uses->add(TypeGuard::class);
            return "return TypeGuard::validateArray($expr, $types);";
        }

        return "return $expr;";
    }
}
