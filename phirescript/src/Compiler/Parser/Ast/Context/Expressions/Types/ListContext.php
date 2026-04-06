<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions\Types;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingAngleBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\OpeningAngleBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\PipeResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ListNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\QueueNode;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ListContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ListNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new OpeningAngleBracketResolver(),
            new TypeResolver(),
            new ClosingAngleBracketResolver(),
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
                $parseContext->contextManager->current()->addChild($this->getChildrenValues());

                $this->node->types = \array_unique($parseContext->contextManager->current()->children);

                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in list definition context!',
            $token->line,
            $token->column,
        );
    }

    public function validation(Token $token, ParseContext $parseContext): void
    {
        if (
            ($token->isEndOfLine() || $token->isRightAngleBracket()) &&
            \count($this->node->types) === 0
        ) {
            throw new CompileException(
                'List must define at least one subtype!',
                $token->line,
                $token->column
            );
        }
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isRightAngleBracket();
    }
}
