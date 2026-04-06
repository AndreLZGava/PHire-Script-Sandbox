<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Declarations\ArrowFunctionDeclarationContext;
use PHireScript\Compiler\Parser\Ast\Context\Signatures\ParameterListContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrowFunctionNode;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsListNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class ArrowFunctionResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return
            $parseContext->tokenManager
            ->sequence()
            ->lookAhead()
            ->once(fn($t) => $t->isOpeningParenthesis())
            ->skipUntil(fn($t) => $t->isClosingParenthesis())
            ->once(fn($t) => $t->isClosingParenthesis())
            ->once(fn($t) => $t->isColon())
            ->skipUntil(fn($t) => $t->isArrow())
            ->once(fn($t) => $t->isArrow())
            ->match();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {

        Debug::show($parseContext->tokenManager->getTokens());
        $arrowFunction = new ArrowFunctionNode($token);

        $arrowFunctionContext = new ArrowFunctionDeclarationContext($arrowFunction);

        $parseContext->contextManager->enter(
            $arrowFunctionContext
        );

        $context->addChild($arrowFunction);
    }
}
