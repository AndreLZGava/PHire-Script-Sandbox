<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class TypeResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return (
            $token->isNull() ||
            $token->isPrimitive() ||
            $token->isSuperType() ||
            $token->isMetaType() ||
            $parseContext->dependencyBuilder->isDependencyOf($parseContext->getCurrentPackage(), $token->value)
        ) &&
            $parseContext->tokenManager->getNextTokenAfterCurrent()->value !== '(';
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $context->addChild($token->value);
    }
}
