<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Scopes;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ScopeContext extends AbstractContext
{
    protected array $symbols = [];

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        return null;
    }

    public function define(string $name, mixed $value): void
    {
        $this->symbols[$name] = $value;
    }

    public function resolve(string $name): mixed
    {
        return $this->symbols[$name] ?? null;
    }
}
