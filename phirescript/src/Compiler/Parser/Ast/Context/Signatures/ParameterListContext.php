<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\ArgumentResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsListNode;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\EmptyArgumentResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ParameterListContext extends AbstractContext
{
    protected array $parameters = [];
    private array $resolvers = [];

    public function __construct(ParamsListNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
            new EmptyArgumentResolver(),
            new ArgumentResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->processProperty();
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in parameters declaration context!',
            $token->line,
            $token->column
        );
    }

    private function processProperty()
    {
        $this->node->params = $this->children;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isComma();
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
