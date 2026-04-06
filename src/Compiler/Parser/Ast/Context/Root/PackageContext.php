<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Root;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\DotResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\IdentifierResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\Ast\Nodes\PackageNode;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;
use PHireScript\Runtime\RuntimeClass;

/**
 * @extends AbstractContext<ParamsNode>
 */
class PackageContext extends AbstractContext
{
    private array $resolvers;

    public function __construct(PackageNode $node)
    {
        parent::__construct($node);
        $this->resolvers = [
            new IdentifierResolver(),
            new DotResolver(),
            new EndOfLineResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);
                $this->node->package .= $this->getChildrenValues() ?? '';
                $this->children = [];

                return null;
            }
        }
        throw new CompileException(
            $token->value . ' is not supported in package definition context!',
            $token->line,
            $token->column,
        );
    }


    public function validation(Token $token, ParseContext $parseContext): void
    {
        if ($token->isEndOfLine()) {
            $this->node->object = $parseContext
                ->tokenManager
                ->getNextAfterFirstFoundElement(RuntimeClass::OBJECT_AS_CLASS)
                ->value;
            $this->node->generateNamespace($parseContext);
            $this->node->validate();
        }
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isEndOfLine();
    }
}
