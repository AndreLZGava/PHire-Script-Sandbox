<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Statements;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Nodes\CommentNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

/**
 * @extends AbstractContext<ParamsNode>
 */
class CommentContext extends AbstractContext
{
    public function handle(Token $token, ParseContext $parseContext): ?Node
    {

        if ($token->isComment()) {
            $node = new CommentNode($token);
            $node->code = \trim((string) $token->value);
            return $node;
        }

        return null;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return true;
    }
}
