<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Root;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\SuperTypeCastingContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\SuperTypeNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class SuperTypeCastingResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isSuperType() && $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $nodeSuperType = new SuperTypeNode($token);
        $parseContext->contextManager->enter(
            new SuperTypeCastingContext($nodeSuperType)
        );

        $parseContext->definePrevious($nodeSuperType);

        $context->addChild($nodeSuperType);
    }
}
