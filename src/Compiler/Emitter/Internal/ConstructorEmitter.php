<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\Internal;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NodeEmitterAbstract;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Helper\Debug\Debug;

class ConstructorEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof ClassNode;
    }

    public function emit(object $class, EmitContext $ctx): string
    {
        $props = \array_filter($class->children, fn($m) => $m instanceof PropertyNode);

        $params = [];
        $assignments = [];

        foreach ($props as $prop) {
            $type = $ctx->types->phpType($prop);
            $params[] = "{$type} \${$prop->name},";
            $assignments[] = $ctx->types->assignment($prop, $ctx->uses);
        }

        $internalStatements = [];
        if (!empty($class->construct->body)) {
            foreach ($class->construct->body as $stmt) {
                $internalStatements[] = $ctx->emitter->emit($stmt, $ctx);
            }
        }

        if (empty($params) && empty($assignments) && empty($internalStatements)) {
            return '';
        }

        return \sprintf(
            "\n    public function __construct(\n        %s\n    ) {\n        %s\n        %s\n    }\n",
            \implode("\n        ", $params),
            \implode("\n        ", $assignments),
            \implode("\n        ", $internalStatements)
        );
    }
}
