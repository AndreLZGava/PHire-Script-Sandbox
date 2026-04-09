<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

class ComplexObjectDefinition extends Node
{
    public string $type;
    public string $name;
}
