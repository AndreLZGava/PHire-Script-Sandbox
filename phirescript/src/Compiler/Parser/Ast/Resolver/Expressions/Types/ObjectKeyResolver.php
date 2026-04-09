<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Expressions\ObjectKeyContext;
use PHireScript\Compiler\Parser\Ast\Resolver\ContextTokenResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\KeyValuePairNode;
use PHireScript\Compiler\Parser\Ast\Nodes\StringNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableReferenceNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\ParseContext;

class ObjectKeyResolver implements ContextTokenResolver
{
    public function isTheCase(Token $token, ParseContext $parseContext, AbstractContext $context): bool
    {
        return (
        $token->isStringLiteral() ||
        $token->isIdentifier()) &&
        $parseContext->tokenManager->getNextTokenAfterCurrent()->isColon();
    }

    public function resolve(
        Token $token,
        ParseContext $parseContext,
        AbstractContext $context
    ): void {
        if ($token->isStringLiteral()) {
            $objectKey = new StringNode($token, $token->value);
        }

        if ($token->isIdentifier()) {
            $objectKey = new StringNode($token, "'" . $token->value . "'");
            $ref = $parseContext->variables->getVariable($token->value);

            if ($ref && $ref?->type?->getRawType() === 'String') {
                $objectKey = new VariableReferenceNode(
                    token: $token,
                    name: $token->value,
                    value: $ref,
                    type: $ref->type,
                );
            }
        }
        $objectKey = new KeyValuePairNode($token, $objectKey, null);
        $parseContext->contextManager->enter(
            new ObjectKeyContext($objectKey)
        );

        $context->addChild($objectKey);
    }
}
