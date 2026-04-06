<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\IgnoreColonResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CommaResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayKeyResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingAngleBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\OpeningAngleBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\PipeResolver;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ArrayContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ArrayLiteralNode $node)
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
                $this->node->types = \array_unique($parseContext->contextManager->current()->children);
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in typed array definition context!',
            $token->line,
            $token->column
        );
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isRightAngleBracket();
    }
}
