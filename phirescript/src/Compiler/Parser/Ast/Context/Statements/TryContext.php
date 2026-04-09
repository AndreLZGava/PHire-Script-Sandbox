<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Statements;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AlwaysResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\HandleResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\OpeningAlwaysScopeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\OpeningTryScopeResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\TryNode;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class TryContext extends AbstractContext
{
    private array $resolvers = [];

    public function __construct(TryNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
        new EndOfLineResolver(),
        'try' => new OpeningTryScopeResolver(),
        'handles[]' => new HandleResolver(),
        'always' => new AlwaysResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $keyResolver => $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->processResolvers($token, $keyResolver);
                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in try declaration context!',
            $token->line,
            $token->column
        );
    }

    private function processResolvers($token, $keyResolver)
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
        return false;
    }
}
