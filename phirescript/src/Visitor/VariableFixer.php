<?php

declare(strict_types=1);

namespace PHireScript\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;

class VariableFixer extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\PropertyFetch && $node->name instanceof Variable) {
            $node->name = new Identifier($node->name->name);
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Variable) {
            $node->class = new Node\Name($node->class->name);
        }
    }
}
