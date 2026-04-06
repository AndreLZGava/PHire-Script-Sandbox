<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Statements;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\VariableConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallNotFoundResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\PrimitiveCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ListResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\MapResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NullLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ObjectLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\QueueResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StackResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\VariableReferenceResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\MetaTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\PrimitiveResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\SuperTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AssignmentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\DotResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\AssignmentNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\ReturnNode;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\GlobalConstantResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ReturnContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ReturnNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new QueueResolver(),
            new StackResolver(),
            new MapResolver(),
            new ListResolver(),
            new PrimitiveCastingResolver(),
            new ArrayResolver(),

            new GlobalConstantResolver(),
            new NullLiteralResolver(),
            new StringLiteralResolver(),
            new NumberLiteralResolver(),
            new ArrayLiteralResolver(),
            new BoolLiteralResolver(),
            new ObjectLiteralResolver(),

            new VariableReferenceResolver(),

            new FunctionCallResolver(),
            new FunctionCallNotFoundResolver(),

            new SuperTypeCastingResolver(),
            new MetaTypeCastingResolver(),

            new DotResolver(),
            new EndOfLineResolver(),
            new CommentResolver(),

            new VariableConsumptionResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleReturn($token);
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported return context!',
            $token->line,
            $token->column
        );
    }

    public function handleReturn($token)
    {
        $this->node->expression = $this->children[0] ?? null;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isEndOfLine() || $token->isComment();
    }
}
