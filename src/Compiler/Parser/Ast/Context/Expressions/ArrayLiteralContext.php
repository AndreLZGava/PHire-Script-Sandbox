<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\IgnoreColonResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CommaResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayKeyResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\BoolLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\RangeResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ArrayLiteralContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(ArrayLiteralNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new CommentResolver(),
            new EndOfLineResolver(),
            new CommaResolver(),
            new IgnoreColonResolver(),
            new ClosingBracketResolver(),
            new ArrayKeyResolver(),

            new RangeResolver(),
            new BoolLiteralResolver(),
            new StringLiteralResolver(),
            new NumberLiteralResolver(),
            new ArrayLiteralResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);

                $resolver->resolve($token, $parseContext, $this);
                $this->node->elements = $this->children;
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in array definition context!',
            $token->line,
            $token->column
        );
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingBracket() || $token->isComment();
    }
}
