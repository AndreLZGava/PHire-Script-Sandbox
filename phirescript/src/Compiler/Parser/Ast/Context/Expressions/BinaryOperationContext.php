<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

/**
 * @extends AbstractContext<ParamsNode>
 */
class BinaryOperationContext extends AbstractContext
{
    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        return null;
    }
}
