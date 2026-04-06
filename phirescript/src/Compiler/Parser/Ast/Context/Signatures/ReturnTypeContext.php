<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\ReturnTypeNode;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\PipeResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ReturnTypeContext extends AbstractContext
{
    protected array $parameters = [];
    private array $resolvers = [];

    public function __construct(ReturnTypeNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
            new TypeResolver(),
            new EndOfLineResolver(),
            new PipeResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);

                $resolver->resolve($token, $parseContext, $this);
                $this->processReturningTypes();
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in definition of return type context!',
            $token->line,
            $token->column
        );
    }

    private function processReturningTypes()
    {
        $this->node->types = $this->children;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $parseContext->tokenManager->getNextTokenAfterCurrent()->isOpeningCurlyBracket() ||
        $parseContext->tokenManager->getNextTokenAfterCurrent()->isEndOfLine();
    }
}
