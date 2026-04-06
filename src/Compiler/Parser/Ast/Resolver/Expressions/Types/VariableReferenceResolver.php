<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\AssignmentContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\Types\QueueContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\AssignmentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\CommentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\QueueNode;
use PHireScript\Compiler\Parser\Ast\Nodes\StringNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableReferenceNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

class VariableReferenceResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->isIdentifier() &&
            $parseContext->variables->getVariable($token->value) &&
            $parseContext->tokenManager->getNextTokenAfterCurrent()->value !== '.';
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        $ref = $parseContext->variables->getVariable($token->value);
        $variableReference = new VariableReferenceNode(
            token: $token,
            name: $token->value,
            value: $ref,
            type: $ref->type,
        );
        $context->addChild($variableReference);
    }
}
