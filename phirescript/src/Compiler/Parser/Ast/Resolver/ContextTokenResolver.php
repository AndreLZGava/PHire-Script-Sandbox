<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

interface ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool;

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void;
}
