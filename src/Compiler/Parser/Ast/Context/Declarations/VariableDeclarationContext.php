<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Declarations;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\AssignmentResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableDeclarationNode;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class VariableDeclarationContext extends AbstractContext
{
    private array $resolvers = [];

    public function __construct(VariableDeclarationNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
            new AssignmentResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in variable declaration context!',
            $token->line,
            $token->column
        );
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isEndOfLine();
    }
}
