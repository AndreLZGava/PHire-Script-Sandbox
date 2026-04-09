<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Root;

use Exception;
use PHireScript\Compiler\Emitter\NodeEmitters\AssignmentEmitter;
use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Context\Declarations\ValidateContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\ClassResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\ImmutableResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\InterfaceResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\TraitResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\VariableConsumptionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\VariableResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallNotFoundResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AssignmentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\DotResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\FunctionCallResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ExternalResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\PackageResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\TypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver as TypesTypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\MetaTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ModifiersResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\PrimitiveResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\SuperTypeCastingResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\UseResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\IfResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\TryResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Resolver\Declaration\ValidateResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\GlobalConstantResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Compiler\Program;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ProgramContext extends AbstractContext {
    private array $resolvers = [];

    public function __construct(ParseContext $parseContext) {
        if ($parseContext->tokenManager->getContext() === 'pre') {
            $this->resolvers = [
                new PackageResolver(),
                new UseResolver(),
                new ExternalResolver(),
            ];
            return;
        }
        $this->resolvers = [
            new GlobalConstantResolver(),
            new CommentResolver(),
            new EndOfLineResolver(),
            new DotResolver(),
            new VariableResolver(),
            new VariableConsumptionResolver(),
            new AssignmentResolver(),
            new FunctionCallResolver(),
            new FunctionCallNotFoundResolver(),

            new TypesTypeResolver(),
            new PrimitiveResolver(),
            new SuperTypeCastingResolver(),
            new MetaTypeCastingResolver(),

            new IfResolver(),
            new TryResolver(),

            // these won't appear in any other sub context
            // declaration
            new PackageResolver(),
            new UseResolver(),
            new ExternalResolver(),
            new ModifiersResolver(),
            // class
            new ClassResolver(),
            new TraitResolver(),
            new TypeResolver(),
            new ImmutableResolver(),
            // interface
            new InterfaceResolver(),
        ];

        if ($parseContext->compilerContext->shouldLoadTestCase()) {
            $this->resolvers[] = new ValidateResolver();
        }
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node {

        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $parseContext->program->statements = $this->children;
                return $parseContext->program;
            }
        }

        if ($parseContext->tokenManager->getContext() === 'pre') {
            return null;
        }

        throw new CompileException(
            $token->value . ' is not supported in program context!',
            $token->line,
            $token->column
        );
    }
}
