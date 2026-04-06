<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\AssignmentContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\Types\QueueContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\AssignmentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\BoolNode;
use PHireScript\Compiler\Parser\Ast\Nodes\CommentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\NumberNode;
use PHireScript\Compiler\Parser\Ast\Nodes\QueueNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class NumberLiteralResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isNumber();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        if (\filter_var($token->value, FILTER_VALIDATE_INT) !== false) {
            $numberNode = new NumberNode($token, \filter_var($token->value, FILTER_VALIDATE_INT));
        } elseif (\filter_var($token->value, FILTER_VALIDATE_FLOAT) !== false) {
            $numberNode = new NumberNode($token, \filter_var($token->value, FILTER_VALIDATE_FLOAT));
        }
        $context->addChild($numberNode);
    }
}
