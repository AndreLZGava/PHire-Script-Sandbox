<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder\Root;

use PHireScript\Compiler\Binder as CompilerBinder;
use PHireScript\Compiler\Binder\Binder;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Program;

class ProgramBinder implements Binder
{
    public function mustBind(Node $node): bool
    {
        return $node instanceof Program;
    }

    public function bind(Node $node, CompilerBinder $binder): void
    {
        foreach ($binder->binders as $check) {
            foreach ($node->statements as $statements) {
                if ($check->mustBind($statements)) {
                    $check->bind($statements, $binder);
                }
            }
        }
    }
}
