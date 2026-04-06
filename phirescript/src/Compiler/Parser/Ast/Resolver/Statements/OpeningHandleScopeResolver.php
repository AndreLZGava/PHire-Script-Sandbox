<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Statements;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Scopes\HandleScopeContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\HandleNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class OpeningHandleScopeResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isOpeningCurlyBracket();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $node = new HandleNode(
            token: $token,
        );

        $parseContext->contextManager->enter(
            new HandleScopeContext($node)
        );
        $context->addChild($node);
    }
}
