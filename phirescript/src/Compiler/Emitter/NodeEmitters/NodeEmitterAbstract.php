<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

class NodeEmitterAbstract
{
    public function removeEndPunctuation(string $text): string
    {
        return \preg_replace('/[!?]+$/', '', $text);
    }
}
