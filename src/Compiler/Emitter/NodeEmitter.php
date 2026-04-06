<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter;

interface NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool;
    public function emit(object $node, EmitContext $ctx): string;
}
