<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\PrimitiveCastingContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\PrimitiveCastingNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class PrimitiveCastingResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isPrimitive() &&
            $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {

        $casting = new PrimitiveCastingNode($token, $token->value);

        $parseContext->contextManager->enter(
            new PrimitiveCastingContext($casting)
        );
        $parseContext->definePrevious($casting);

        $context->addChild($casting);
    }
}
