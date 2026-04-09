<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ClassExtendsNode extends ComplexObjectDefinition
{
    public function __construct(public Token $token, public string $child = '')
    {
    }
}
