<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\Types\StackContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\StackNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class StackResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->value === 'Stack';
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $node = new StackNode($token);

        $parseContext->contextManager->enter(
            new StackContext($node)
        );
        $parseContext->definePrevious($node);

        $context->addChild($node);
    }
}
