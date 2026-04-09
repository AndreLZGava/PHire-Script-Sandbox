<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\Types\QueueContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\BinaryExpressionNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;

/**
 * @todo implement it to support binary expressions
 */
class NumericExpressionResolver implements ContextTokenResolver
{
    public const MATH_OPERATORS = [
    '*',
    '/',
    '-',
    '+',
    '**',
    '%'
    ];

    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        $next = $parseContext->tokenManager->getNextTokenAfterCurrent();
        $hasOperator = \in_array($next->value, self::MATH_OPERATORS);

        return ($token->isNumber() && $hasOperator) ||
        (
          $token->isIdentifier() &&
          $parseContext->variables->getVariable($token->value) &&
          $hasOperator
        ) ||
        (
          $token->isOpeningParenthesis() &&
          (
            $next->isNumber() ||
            $token->isIdentifier() &&
            $parseContext->variables->getVariable($next->value)
          )
        );
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
      // this must create a BinaryExpressionNode
        $expression = new BinaryExpressionNode($token);
      // this must create a new context
      // related to src/Compiler/Parser/IdentifyTokenFactories/Symbols/VariableAssignmentFactory.php
        $parseContext->contextManager->enter(
            new QueueContext($expression)
        );

      // $parseContext->definePrevious($expression);

      // $context->addChild($expression);
    }
}
