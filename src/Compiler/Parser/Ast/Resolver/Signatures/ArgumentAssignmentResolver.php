<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Signatures\ArgumentAssignmentContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\AssignmentNode;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamArgumentNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class ArgumentAssignmentResolver implements ContextTokenResolver
{
    public function __construct(protected ParamArgumentNode $node)
    {
    }

    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return $token->value === '=';
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {

        $assignment = new AssignmentNode(token: $token, left: $this->node);

        $parseContext->contextManager->enter(
            new ArgumentAssignmentContext($assignment)
        );

        $context->addChild($assignment);
    }
}
