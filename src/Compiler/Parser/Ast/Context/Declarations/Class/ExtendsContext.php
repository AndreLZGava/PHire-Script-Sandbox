<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Declarations\Class;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ComplexObjects\IdentifierResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassExtendsNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ExtendsContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ClassExtendsNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
        new EndOfLineResolver(),
        new IdentifierResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleClassProperties($token, $keyResolver);

                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in extending for class context!',
            $token->line,
            $token->column,
        );
    }

    private function handleClassProperties(Token $token, int|string $keyResolver): void
    {

        $this->node->child = $this->children[0] ?? null;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $parseContext->tokenManager->getNextTokenAfterCurrent()->isKeyword() ||
        $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningCurlyBracket();
    }
}
