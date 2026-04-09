<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context;

use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

/**
 * @template T of Node
 */
abstract class AbstractContext
{
    private ?AbstractContext $parent = null;
    public array $children = [];

    /**
     * @var T
     */
    public $node;

    /**
     * @param T $node
     */
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public function setParent(AbstractContext $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?AbstractContext
    {
        return $this->parent;
    }

    abstract public function handle(Token $token, ParseContext $parseContext): ?Node;

    public function canClose(Token $token, ParseContext $parseContext): bool
    {
        return false;
    }

    public function validation(Token $token, ParseContext $parseContext): void
    {
    }

    public function afterClose(Token $token, ParseContext $parseContext): void
    {
        return;
    }

    public function onClose(Token $token, ParseContext $parseContext): void
    {
        return;
    }

    public function addChild($child): void
    {
        if (!empty($child)) {
            $this->children[] = $child;
        }
    }

    protected function getChildrenValues(?string $key = null)
    {
        if ($key && \str_contains('[]', $key)) {
            return $this->children;
        }
        return \current($this->children) ?? null;
    }

    protected function sanitizeKeys($key)
    {
        return \str_replace('[]', '', $key);
    }
}
