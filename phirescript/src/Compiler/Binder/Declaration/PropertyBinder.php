<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder\Declaration;

use PHireScript\Compiler\Binder as CompilerBinder;
use PHireScript\Compiler\Binder\Binder;
use PHireScript\Compiler\Parser\Ast\Nodes\InterfaceMethodDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Helper\Debug\Debug;

class PropertyBinder implements Binder
{
    public function mustBind(Node $node): bool
    {
        return $node instanceof PropertyNode;
    }

    public function bind(Node $node, CompilerBinder $binder): void
    {
        foreach ($binder->binders as $check) {
            foreach ($node->body?->children ?? [] as $statements) {
                if ($check->mustBind($statements)) {
                    $check->bind($statements, $binder);
                }
            }
        }
    }
}
