<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class InterfaceNode extends ComplexObjectDefinition
{
    public array $modifiers = [];
    public ?InterfaceExtendsNode $extends = null;
    public ?InterfaceBodyNode $body = null;
    public function __construct(public Token $token)
    {
    }
}
