<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Emitter\NodeEmitters;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\NodeEmitter;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Program;
use PHireScript\Helper\Debug\Debug;

class ProgramEmitter extends NodeEmitterAbstract implements NodeEmitter
{
    public function supports(object $node, EmitContext $ctx): bool
    {
        return $node instanceof Program;
    }

    public function emit(object $node, EmitContext $ctx): string
    {
        //Debug::show($node->statements);exit;
        $code['init'] = "<?php\n\n";
        foreach ($node->statements as $position => $stmt) {
            $code[\get_class($stmt) . '_' . $position] = $ctx->emitter->emit($stmt, $ctx);
        }
        $uses = $this->processUses($ctx);

        return $this->processEntireCode($code, $uses);
    }

    private function processEntireCode(array $arrayCode, string $uses)
    {
        $processedCodeBeforeUses = "";
        $processedCodeAfterUses = "";
        foreach ($arrayCode as $key => $code) {
            if (
                $key === 'init' ||
                \str_contains($key, "PHireScript\Compiler\Parser\Ast\Nodes\PackageNode")
            ) {
                $processedCodeBeforeUses .= $code . "\n";
                continue;
            }
            $processedCodeAfterUses .= $code . "\n";
        }
        return $processedCodeBeforeUses . $uses . $processedCodeAfterUses;
    }

    private function processUses(EmitContext $ctx)
    {
        $useStatements = "";
        $uses = $ctx->uses->getUses();
        if (!empty($uses)) {
            foreach ($uses as $class => $val) {
                $useStatements .= "use {$class};\n";
            }
            $useStatements .= "\n";
        }
        return $useStatements;
    }
}
