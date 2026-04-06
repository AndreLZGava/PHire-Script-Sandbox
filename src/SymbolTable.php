<?php

declare(strict_types=1);

namespace PHireScript;

class SymbolTable
{
    private array $scopes = [[]];

    private array $functionReturns = [];
    private array $typeDefinitions = [];

    private $functions = [];

    public function __construct()
    {
        $this->registerBuiltins();
    }

    public function registerTypeDefinition(string $name, $node)
    {
        $this->typeDefinitions[$name] = $node;
    }

    public function getTypeDefinition(string $name)
    {
        return $this->typeDefinitions[$name] ?? null;
    }

    public function enterScope()
    {
        \array_push($this->scopes, []);
    }

    public function getAllScopes()
    {
        return ($this->scopes);
    }

    public function exitScope()
    {
        \array_pop($this->scopes);
    }

    public function setType($name, $type, $linePosition)
    {
        $this->scopes[$name][$linePosition] = $type;
    }

    public function getType($name, $linePosition)
    {
        for ($i = \count($this->scopes) - 1; $i >= 0; $i--) {
            if (isset($this->scopes[$name][$linePosition])) {
                return $this->scopes[$name][$linePosition];
            }
        }
        return 'UNKNOWN';
    }

    public function registerBuiltins()
    {
        $this->functionReturns = [
        'toUpperCase' => 'STRING',
        'toLowerCase' => 'STRING',
        'join'        => 'STRING',
        'push'        => 'ARRAY',
        ];
    }

    public function registerFunction($name)
    {
        $this->functions[$name] = true;
    }

    public function isFunction($name)
    {
        return isset($this->functions[$name]);
    }
}
