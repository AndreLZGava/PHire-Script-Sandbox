<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use Exception;
use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CastingConsumptionParams\ClosingParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CastingConsumptionParams\OpeningParamsConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\VariableReferenceResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\PrimitiveCastingNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class PrimitiveCastingContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(PrimitiveCastingNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new OpeningParamsConsumptionResolver(),
            new ClosingParamsConsumptionResolver(),

            new StringLiteralResolver(),
            new BoolLiteralResolver(),
            new NumberLiteralResolver(),

            new VariableReferenceResolver(),

            new EndOfLineResolver(),
            new CommentResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $param = null;
                if (!empty($this->children) && !empty($this->children[0]->params)) {
                    $param = $this->children[0]->params[0];
                }
                $this->node->value = $param;

                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in casting definition context!',
            $token->line,
            $token->column
        );
    }

    public function validation(Token $token, ParseContext $parseContext): void
    {
        if (
            ($token->isEndOfLine() || $token->isClosingParenthesis()) &&
            is_null($this->node->value)
        ) {
            throw new CompileException(
                'Primitive casting value to ' . $this->node->to .
                    ' must receive at least one parameter!',
                $this->node->token->line,
                $this->node->token->column
            );
        }
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        $parseContext->contextManager->exit();
        return;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isEndOfLine() || $token->isComment();
    }
}
