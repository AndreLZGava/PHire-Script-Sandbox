<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Scopes;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\ConsumptionParams\OpeningParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\OpeningArgumentConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\OpeningParamsDeclarationResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\OpeningHandleScopeResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Compiler\Parser\Ast\Nodes\HandleNode;
use PHireScript\Helper\Debug\Debug;

/**
 * @extends AbstractContext<ParamsNode>
 */
class HandleContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(HandleNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            'param' => new OpeningArgumentConsumptionResolver(),
            'children[]' => new OpeningHandleScopeResolver(),
            new EndOfLineResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleProperties($token, $keyResolver);

                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in handle definition context!',
            $token->line,
            $token->column,
        );
    }

    private function handleProperties($token, $keyResolver)
    {
        if (\is_int($keyResolver)) {
            return;
        }
        $key = $this->sanitizeKeys($keyResolver);
        $value = $this->getChildrenValues($keyResolver);
        if (\str_contains($keyResolver, '[]')) {
            $this->node->$key[] =  $value ?: [];
            $this->children = [];
            return;
        }
        $this->node->$key =  $value ?: [];
        $this->children = [];
        return;
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        if ($token->isClosingCurlyBracket()) {
            $parseContext->contextManager->exit();
        }
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingCurlyBracket() ||
            $parseContext->tokenManager
                ->getNextTokenAfterCurrent()->value === 'always';
    }
}
