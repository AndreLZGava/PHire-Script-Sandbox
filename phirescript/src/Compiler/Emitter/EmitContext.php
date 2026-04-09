<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter;

use PHireScript\Compiler\Emitter\Type\PhpTypeResolver;
use PHireScript\DependencyGraphBuilder;

class EmitContext
{
    public function __construct(
        public readonly bool $dev,
        public readonly UseRegistry $uses,
        public PhpTypeResolver $types,
        public DependencyGraphBuilder $dependencyManager,
        public readonly EmitterDispatcher $emitter,
        public bool $insideInterface = false,
        public bool $insideClass = false,
        public bool $insideMethodSignature = false,
        public ?string $currentMethodReturnType = null,
    ) {
    }
}
