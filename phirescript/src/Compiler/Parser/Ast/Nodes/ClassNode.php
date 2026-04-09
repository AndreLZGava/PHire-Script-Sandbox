<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class ClassNode extends ComplexObjectDefinition
{
    public string $type;
    public bool $readOnly = false;
    public array $modifiers = [];
    public ?string $docBlock = null;
    public ?ClassExtendsNode $extends = null;
    public ?WithNode $with = null;
    public ?ImplementsNode $implements = null;
    public ?ConstructorDefinition $construct = null;
    public array $inject = [];
    public array $cache = [];
    public array $schedule = [];
    public ?ClassBodyNode $body = null;
    public ?DependencyInjectionNode $typeDependencyInjection = null;

    public function __construct(public Token $token)
    {
        $this->type = $token->value;
    }
}
