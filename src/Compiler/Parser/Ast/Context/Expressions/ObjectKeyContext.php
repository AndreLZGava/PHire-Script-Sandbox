<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Expressions;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\IgnoreColonResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CommaResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ArrayLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingCurlyBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\NumberLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\StringLiteralResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\VariableReferenceResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\KeyValuePairNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ObjectKeyContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(KeyValuePairNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
            new CommentResolver(),
            new IgnoreColonResolver(),
            new EndOfLineResolver(),
            new ClosingCurlyBracketResolver(),
            new CommaResolver(),

            new StringLiteralResolver(),
            new ArrayLiteralResolver(),
            new NumberLiteralResolver(),
            new VariableReferenceResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->node->value = $this->children[0] ?? null;
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in object key definition context!',
            $token->line,
            $token->column
        );
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        if ($token->isClosingCurlyBracket()) {
            $parseContext->contextManager->exit();
        }
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isComma() || $token->isComment() || $token->isClosingCurlyBracket();
    }
}
