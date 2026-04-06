<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Scopes\IfConditionContext;
use PHireScript\Compiler\Parser\Ast\Context\Signatures\ParameterListContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\IfConditionNode;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsListNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class OpeningIfConditionResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isOpeningParenthesis();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $idCondition = new IfConditionNode($token);

        $parseContext->contextManager->enter(
            new IfConditionContext($idCondition)
        );
        $context->addChild($idCondition);
    }
}
