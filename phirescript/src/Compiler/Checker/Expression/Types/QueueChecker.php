<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Checker\Expression\Types;

use PHireScript\Compiler\Checker\Checker;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\QueueNode;
use PHireScript\Compiler\Checker as CompilerChecker;

class QueueChecker extends Checker
{
    public function mustCheck(Node $node): bool
    {
        return $node instanceof QueueNode;
    }

    public function check(Node $node, CompilerChecker $checker): void
    {
        return;
    }
}
