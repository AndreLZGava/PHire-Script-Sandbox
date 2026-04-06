<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Declarations;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\IdentifierResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Class\ExtendsResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Class\ImplementsResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Class\WithResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\ModifiersResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Class\ClassBodyResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Class\DependencyInjectionResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ClassContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ClassNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            'name' => new IdentifierResolver(),
            'body[]' => new ClassBodyResolver(),
            new EndOfLineResolver(),
            new CommentResolver(),
            'extends' => new ExtendsResolver(),
            'with' => new WithResolver(),
            'implements' => new ImplementsResolver(),
            'typeDependencyInjection' => new DependencyInjectionResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        $this->handleModifiers($parseContext->consumePrevious());
        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->handleClassProperties($token, $keyResolver);

                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in ' . $this->node->token->value . ' definition context!',
            $token->line,
            $token->column,
        );
    }

    private function handleModifiers($previousModifiers)
    {
        $modifiers = $previousModifiers ? ModifiersResolver::getModifiers($previousModifiers) : [];
        if (!empty($modifiers)) {
            $this->node->modifiers = $modifiers;
        }
    }

    private function handleClassProperties(Token $token, int|string $keyResolver): void
    {
        if (\is_int($keyResolver)) {
            return;
        }
        $key = $this->sanitizeKeys($keyResolver);
        $value = $this->getChildrenValues($keyResolver);
        $this->node->$key =  $value ?: [];
        $this->children = [];
        return;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingCurlyBracket();
    }
}
