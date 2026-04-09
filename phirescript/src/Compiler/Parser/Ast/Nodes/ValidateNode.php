<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ValidateNode extends ComplexObjectDefinition
{
    public string $type;
    public bool $readOnly = false;
    public array $modifiers = [];
    public ?string $docBlock = null;
    public ?ClassExtendsNode $extends = null;
    public ?WithNode $with = null;
    public ?ImplementsNode $implements = null;
    public ?ConstructorDefinition $construct = null;
    public ?ValidateBodyNode $body = null;

    public function __construct(public Token $token)
    {
        $this->type = $token->value;
    }
}
