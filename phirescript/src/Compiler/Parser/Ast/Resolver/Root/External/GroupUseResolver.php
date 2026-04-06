<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Root\External;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Root\GroupUseContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\GroupUseNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class GroupUseResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isOpeningCurlyBracket();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $node = new GroupUseNode(
            token: $token,
        );

        $parseContext->contextManager->enter(
            new GroupUseContext($node)
        );

        $context->addChild($node);
    }
}
