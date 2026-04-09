<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder\Declaration;

use PHireScript\Compiler\Binder as CompilerBinder;
use PHireScript\Compiler\Binder\Binder;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\InterfaceNode;

class InterfaceBinder implements Binder
{
    public function mustBind(Node $node): bool
    {
        return $node instanceof InterfaceNode;
    }

    public function bind(Node $node, CompilerBinder $binder): void
    {
        foreach ($binder->binders as $check) {
            foreach ($node->body?->children ?? [] as $children) {
                if ($check->mustBind($children)) {
                    $check->bind($children, $binder);
                }
            }
        }
    }
}
