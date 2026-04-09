<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\ConsumptionParams\ClosingParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\ConsumptionParams\OpeningParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallNotFoundResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\DotResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\FunctionNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class FunctionCallContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(FunctionNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new OpeningParamsConsumptionResolver(),
            new ClosingParamsConsumptionResolver(),
            new EndOfLineResolver(),
            new DotResolver(),
            new FunctionCallResolver(),
            new FunctionCallNotFoundResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleParameters($resolver, $parseContext, $token);
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in method consumption context!',
            $token->line,
            $token->column
        );
    }

    private function handleParameters($resolver, $parseContext, $token)
    {
        $this->node->params = $this->getChildrenValues();
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        if ($token->isEndOfLine()) {
            $parseContext->contextManager->exit();
        }
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        /**
         * myTest.myFunction().myAnotherFunction()
         * .myAnotherFunction().
         * myNewMethod()
         */
        return $token->isDot() || $token->isEndOfLine();
    }
}
