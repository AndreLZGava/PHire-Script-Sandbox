<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Statements;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Scopes\IfScopeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Scopes\MethodScopeResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\OpeningIfConditionResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Signatures\OpeningParamsDeclarationResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\IfNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class IfContext extends AbstractContext
{
    private array $resolvers = [];

    public function __construct(IfNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
        new EndOfLineResolver(),
        'condition' => new OpeningIfConditionResolver(),
        'statements' => new IfScopeResolver(),
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
            $token->value . ' is not supported in if declaration context!',
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
        $this->node->$key =  $value ?: [];
        $this->children = [];
        return;
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return false;
    }
}
