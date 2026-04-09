<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Signatures\ReturnTypeContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ReturnTypeNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class ReturnTypeResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isColon();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $returnType = new ReturnTypeNode($token);

        $parseContext->contextManager->enter(
            new ReturnTypeContext($returnType)
        );
        $context->addChild($returnType);
    }
}
