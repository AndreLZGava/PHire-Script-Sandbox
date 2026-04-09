<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder\Declaration\Class;

use PHireScript\Compiler\Binder as CompilerBinder;
use PHireScript\Compiler\Binder\Binder;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\CustomClasses\MagicMethods;

class MagicMethodDeclarationBinder implements Binder
{
    public function mustBind(Node $node): bool
    {
        return $node instanceof MethodDeclarationNode && $node->token->isMagicMethod();
    }

    public function bind(Node $node, CompilerBinder $binder): void
    {
        $magicMethods = new MagicMethods();
        $magicMethodName = $node->name;
        $node->implements = $magicMethods->$magicMethodName();

        foreach ($binder->binders as $check) {
            foreach ($node->body?->children ?? [] as $statements) {
                if ($check->mustBind($statements)) {
                    $check->bind($statements, $binder);
                }
            }
        }
    }
}
