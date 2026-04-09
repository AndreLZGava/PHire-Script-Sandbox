<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Declarations;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\ConsumptionParams\ClosingParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CommaResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\VariableReferenceResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\ParamsNode;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ParamsConsumptionContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ParamsNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new StringLiteralResolver(),
            new NumberLiteralResolver(),
            new BoolLiteralResolver(),
            new ArrayLiteralResolver(),
            new VariableReferenceResolver(),

            new ClosingParamsConsumptionResolver(),
            new CommaResolver(),

            new EndOfLineResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->node->params = $this->children;
                return null;
            }
        }
        // Debug::show($parseContext->tokenManager->getProcessedTokens(10));exit;
        throw new CompileException(
            $token->value . ' is not supported in params context!',
            $token->line,
            $token->column
        );
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        //$parseContext->contextManager->exit();
        return;
    }


    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingParenthesis();
    }
}
