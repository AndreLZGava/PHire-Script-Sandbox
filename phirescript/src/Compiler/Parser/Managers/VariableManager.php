<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers;

use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableReferenceNode;
use PHireScript\Helper\Debug\Debug;

class VariableManager
{
    private mixed $variableOnFocus = null;
    public function __construct(
        private array $variables = [],
        private array $properties = [],
    ) {
    }

    public function addProperty(PropertyNode $property)
    {
        $this->properties[$property->name] = $property;
    }

    public function addVariable(VariableDeclarationNode|VariableReferenceNode $variable)
    {
        $this->variables[$variable->name] = $variable;
        $this->variableOnFocus = $variable;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function getVariable(string $variableName): null|VariableDeclarationNode|VariableReferenceNode
    {
        if (isset($this->variables[$variableName])) {
            $this->variableOnFocus = $this->variables[$variableName];
        }

        return  $this->variables[$variableName] ?? null;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getProperty(string $propertyName): ?PropertyNode
    {
        return $this->properties[$propertyName]  ?? null;
    }

    public function getVariableOnFocus()
    {
        return $this->variableOnFocus;
    }

    public function setVirtualVariable($variable)
    {
        $this->variableOnFocus = $variable;
    }
}
