<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\NewExceptionNode;

class NewExceptionEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof NewExceptionNode;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        $className = '\\' . \ltrim($node->className, '\\');

        $message = \addslashes($node->message);

        return "new {$className}(\"{$message}\")";
    }
}
