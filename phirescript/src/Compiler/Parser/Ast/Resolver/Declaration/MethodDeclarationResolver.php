<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Declaration;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Declarations\MethodDeclarationContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ModifiersResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodDeclarationNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class MethodDeclarationResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return ($token->isIdentifier() || $token->isMagicMethod())
            && $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningParenthesis();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $modifiers = $this->handleModifiers($parseContext->consumePrevious());
        $method = new MethodDeclarationNode(
            token: $token,
            name: $token->value,
            modifiers: empty($modifiers) ? ['public'] : $modifiers,
            mustBeBool: \str_ends_with('?', $token->value),
            mustBeVoid: \str_ends_with('!', $token->value),
        );

        $parseContext->contextManager->enter(
            new MethodDeclarationContext($method)
        );

        $context->addChild($method);
    }

    private function handleModifiers($previousModifiers)
    {
        $modifiers = $previousModifiers ? ModifiersResolver::getModifiers($previousModifiers) : [];
        return $modifiers ?? [];
    }
}
