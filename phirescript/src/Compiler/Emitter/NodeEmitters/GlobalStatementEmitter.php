<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\GlobalStatement;

class GlobalStatementEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof GlobalStatement;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $code = \rtrim($node->code);

        if (!\str_ends_with($code, "\n")) {
            $code .= "\n";
        }

        return $this->indent($code);
    }

    private function indent(string $code): string
    {
        return "    " . $code;
    }
}
