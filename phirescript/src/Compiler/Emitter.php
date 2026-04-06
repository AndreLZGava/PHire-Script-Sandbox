<?php

declare(strict_types=1);

namespace PHireScript\Compiler;

use PHireScript\Compiler\Emitter\EmitContext;
use PHireScript\Compiler\Emitter\EmitterDispatcher;
use PHireScript\Compiler\Emitter\Internal\AlwaysEmitter;
use PHireScript\Compiler\Emitter\Internal\HandleEmitter;
use PHireScript\Compiler\Emitter\Internal\IfStatementEmitter;
use PHireScript\Compiler\Emitter\Internal\IssetOperatorEmitter;
use PHireScript\Compiler\Emitter\Internal\NewExceptionEmitter;
use PHireScript\Compiler\Emitter\Internal\NotOperatorEmitter;
use PHireScript\Compiler\Emitter\Internal\ThrowStatementEmitter;
use PHireScript\Compiler\Emitter\Internal\TryEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ArrayLiteralEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\AssignmentEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\BinaryExpressionEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\BoolEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\CastingEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ClassBodyEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\PropertyEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ClassEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\CommentStatementEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\UseEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ExternalEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\FunctionEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\GlobalConstEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\GlobalStatementEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\InterfaceBodyEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\InterfaceEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\InterfaceMethodEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\KeyValuePairEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ListEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\LiteralEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\MapEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\MethodEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\MethodScopeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NullEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\NumberEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ObjectLiteralEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\PackageEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ParamArgumentEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ParameterEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ParamsListEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ProgramEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\PropertyAccessEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\PropertyDeclarationEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\QueueEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\RangeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ReturnEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ReturnTypeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\StackEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\VariableReferenceAssignEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\StringEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\SuperTypeEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\ThisExpressionEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\TraitEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\VariableDeclarationEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\VariableEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\VoidExpressionEmitter;
use PHireScript\Compiler\Emitter\NodeEmitters\WithEmitter;
use PHireScript\Compiler\Emitter\Type\PhpTypeResolver;
use PHireScript\Compiler\Emitter\UseRegistry;
use PHireScript\DependencyGraphBuilder;

class Emitter
{
    private readonly EmitterDispatcher $dispatcher;

    public function __construct(private array $config, private DependencyGraphBuilder $dependencyManager)
    {
        $this->dispatcher = new EmitterDispatcher([
            new ProgramEmitter(),
            new PackageEmitter(),
            new UseEmitter(),
            new ExternalEmitter(),

            new GlobalConstEmitter(),

            new InterfaceEmitter(),
            new InterfaceBodyEmitter(),
            new ClassEmitter(),
            new ClassBodyEmitter(),
            new TraitEmitter(),
            new InterfaceMethodEmitter(),
            new MethodEmitter(),
            new ReturnTypeEmitter(),
            new MethodScopeEmitter(),
            new FunctionEmitter(),
            new WithEmitter(),

            new ObjectLiteralEmitter(),

            new ReturnEmitter(),
            new KeyValuePairEmitter(),
            new ArrayLiteralEmitter(),
            new LiteralEmitter(),
            new NullEmitter(),
            new RangeEmitter(),
            new BoolEmitter(),
            new StringEmitter(),
            new NumberEmitter(),
            new BinaryExpressionEmitter(),
            new VariableReferenceAssignEmitter(),
            new SuperTypeEmitter(),
            new QueueEmitter(),
            new ListEmitter(),
            new StackEmitter(),
            new MapEmitter(),

            new PropertyDeclarationEmitter(),
            new VoidExpressionEmitter(),
            new VariableEmitter(),
            new VariableDeclarationEmitter(),
            new AssignmentEmitter(),
            new ThisExpressionEmitter(),
            new PropertyAccessEmitter(),

            new ParameterEmitter(),
            new PropertyEmitter(),

            new CommentStatementEmitter(),
            new GlobalStatementEmitter(),
            new IfStatementEmitter(),
            new TryEmitter(),
            new HandleEmitter(),
            new AlwaysEmitter(),
            new IssetOperatorEmitter(),
            new NotOperatorEmitter(),
            new ThrowStatementEmitter(),
            new NewExceptionEmitter(),
            new CastingEmitter(),
            new ParamsListEmitter(),
            new ParamArgumentEmitter(),
        ]);
    }

    public function emit(Program $program): string
    {
        $useRegistry = new UseRegistry();

        $context = new EmitContext(
            dev: $this->config['dev'] ?? false,
            uses: $useRegistry,
            emitter: $this->dispatcher,
            types: new PhpTypeResolver($this->config),
            dependencyManager: $this->dependencyManager,
        );

        return $this->dispatcher->emit($program, $context);
    }
}
