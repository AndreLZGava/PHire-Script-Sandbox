<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Signatures\ParameterListContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsListNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class OpeningParamsDeclarationResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        $before = $parseContext->tokenManager->getPreviousTokenBeforeCurrent();
        return $token->isOpeningParenthesis() &&
            (
                $before->isIdentifier() || $before->isMagicMethod()
                //@todo probably will need a resolver only for arrow function cases instead of this or past was a openingParenthesis
            ) || $before->isOpeningParenthesis() && $token->isType();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $paramsListNode = new ParamsListNode($token);

        $parseContext->contextManager->enter(
            new ParameterListContext($paramsListNode)
        );
        $context->addChild($paramsListNode);
    }
}
