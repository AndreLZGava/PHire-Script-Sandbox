<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Context\Signatures;

use PHireScript\Compiler\Parser\Ast\Context\AbstractContext;
use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Compiler\Parser\ParseContext;

/**
 * @extends AbstractContext<ParamsNode>
 */
class ModifiersContext extends AbstractContext
{
    protected array $modifiers = [];

    public function handle(Token $token, ParseContext $parseContext): ?Node
    {
        return null;
    }

    public function addModifier(string $modifier): void
    {
        $this->modifiers[] = $modifier;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }
}
