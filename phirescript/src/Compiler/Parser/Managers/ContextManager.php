<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Helper\Debug\Debug;

class ContextManager
{
    private AbstractContext $current;
    private $path;
    private $config;

    public function __construct(AbstractContext $root)
    {
        $this->current = $root;
    }

    public function current(): AbstractContext
    {
        return $this->current;
    }

    public function enter(AbstractContext $context): AbstractContext
    {
        $context->setParent($this->current);
        $this->current = $context;

        return $context;
    }

    public function exit(): void
    {
        if ($this->current->getParent() !== null) {
            $this->current = $this->current->getParent();
        }
    }

    public function exitUntil(string $contextClass): void
    {
        while (
            $this->current->getParent() !== null &&
            !$this->current instanceof $contextClass
        ) {
            $this->current = $this->current->getParent();
        }
    }

    public function isIn(string $contextClass): bool
    {
        $cursor = $this->current;

        while ($cursor !== null) {
            if ($cursor instanceof $contextClass) {
                return true;
            }

            $cursor = $cursor->getParent();
        }

        return false;
    }

    public function handle(Token $token, $parseContext): void
    {
        $this->current->handle($token, $parseContext);
        $this->current->validation($token, $parseContext);
        $current = $this->current;
        if ($this->current->canClose($token, $parseContext)) {
            $parseContext->consumePrevious();
            $this->current->onClose($token, $parseContext);
            $this->exit();
            $current->afterClose($token, $parseContext);
        }
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
