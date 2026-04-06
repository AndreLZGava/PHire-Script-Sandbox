<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CastingConsumptionParams;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Declarations\ParamsConsumptionContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class OpeningParamsConsumptionResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        $beforeCurrent = $parseContext->tokenManager->getPreviousTokenBeforeCurrent();
        return $token->isOpeningParenthesis() &&
            $beforeCurrent->isPrimitive() || $beforeCurrent->isSuperType() || $beforeCurrent->isMetaType();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $paramsNode = new ParamsNode($token);

        $parseContext->contextManager->enter(
            new ParamsConsumptionContext($paramsNode)
        );
        $context->addChild($paramsNode);
    }
}
