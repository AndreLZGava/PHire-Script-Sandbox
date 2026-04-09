<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Root;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Use\AsResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\CommaResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Expressions\Types\ClosingCurlyBracketResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Root\Use\IdentifierResolver;
use PHireScript\Compiler\Parser\Ast\Resolver\Statements\EndOfLineResolver;
use PHireScript\Compiler\Parser\Ast\Nodes\GroupUseNode;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;
use PHireScript\Runtime\Exceptions\CompileException;

/**
 * @extends AbstractContext<ParamsNode>
 */
class GroupUseContext extends AbstractContext
{
    private array $resolvers;
    public bool $alias = false;
    public bool $shouldProcessAsAlias = false;

    public function __construct(GroupUseNode $node)
    {
        parent::__construct($node);

        $this->resolvers = [
        new IdentifierResolver(),
        new CommaResolver(),
        new AsResolver(),
        new EndOfLineResolver(),
        new ClosingCurlyBracketResolver(),
        ];
    }

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        foreach ($this->resolvers as $key => $resolver) {
            $this->shouldProcessAsAlias = $this->alias;
            if ($resolver->isTheCase($token, $parseContext, $this)) {
                $token->processedBy = \get_class($resolver);
                $resolver->resolve($token, $parseContext, $this);

                $this->handleSaveGroup($token);

                return null;
            }
        }

        throw new CompileException(
            $token->value . ' is not supported in group use definition context!',
            $token->line,
            $token->column,
        );
    }

    private function handleSaveGroup($token)
    {
        $parts = $this->node->parts;
        if (!$this->shouldProcessAsAlias) {
            foreach ($this->children as $key => $item) {
                if (!\in_array($item, $parts) && !\array_key_exists($item, $parts)) {
                    $parts[] = $item;
                }
            }
            $this->node->parts = $parts;
            return;
        }
        $lastItem = \array_pop($parts);
        $aliasName = \end($this->children);
        $parts[$aliasName] = $lastItem;
        $this->node->parts = \array_unique($parts);
    }

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return $token->isClosingCurlyBracket();
    }
}
