<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Scopes;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\MethodDeclarationResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\PropertyResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\VariableConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\VariableResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallNotFoundResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingCurlyBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NullLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ObjectLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\PrimitiveCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ModifiersResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AssignmentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\DotResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodScopeNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver as TypesTypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ClosingCurlyBracketResolver as RootClosingCurlyBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\MetaTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\PrimitiveResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\SuperTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\ClosingParamsDeclarationResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\IfResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\ReturnResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\IfConditionNode;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\GlobalConstantResolver;

/**
 * @extends AbstractContext<ParamsNode>
 */
class IfConditionContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(IfConditionNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new CommentResolver(),
            new EndOfLineResolver(),
            new DotResolver(),
            new VariableResolver(),
            new VariableConsumptionResolver(),
            new AssignmentResolver(),
            new FunctionCallResolver(),
            new FunctionCallNotFoundResolver(),

            new PrimitiveCastingResolver(),
            new ArrayResolver(),

            new GlobalConstantResolver(),
            new NullLiteralResolver(),
            new StringLiteralResolver(),
            new NumberLiteralResolver(),
            new ArrayLiteralResolver(),
            new BoolLiteralResolver(),
            new ObjectLiteralResolver(),

            new TypesTypeResolver(),
            new PrimitiveResolver(),
            new SuperTypeCastingResolver(),
            new MetaTypeCastingResolver(),

            new ClosingParamsDeclarationResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {

        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleConditions($token, $keyResolver);

                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in if condition definition context!',
            $token->line,
            $token->column,
        );
    }

    private function handleConditions(Token $token, int|string $keyResolver): void
    {
        $this->node->children = $this->children;
    }


    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingParenthesis();
    }
}
