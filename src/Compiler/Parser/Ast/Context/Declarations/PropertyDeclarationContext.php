<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Declarations;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\IdentifierResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AssignmentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\CommentResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\TypeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\PipeResolver;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class PropertyDeclarationContext extends AbstractContext
{
    private array $resolvers = [];

    public function __construct(PropertyNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
            'types[]' => new TypeResolver(),
            'name' => new IdentifierResolver(),
            new PipeResolver(),
            new EndOfLineResolver(),
            new AssignmentResolver(),
            new CommentResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->processProperty($token, $keyResolver);
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in property declaration context!',
            $token->line,
            $token->column
        );
    }

    private function processProperty(Token $token, int|string $keyResolver)
    {
        if (\is_int($keyResolver)) {
            return;
        }
        $key = $this->sanitizeKeys($keyResolver);
        $value = $this->getChildrenValues($keyResolver);
        if (\str_contains($keyResolver, '[]')) {
            $this->node->$key[] =  $value ?: [];
            $this->children = [];
            return;
        }
        $this->node->$key =  $value ?: [];
        $this->children = [];
        return;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isEndOfLine();
    }
}
