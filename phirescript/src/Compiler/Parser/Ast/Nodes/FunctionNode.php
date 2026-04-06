<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;

class FunctionNode extends Node
{
    private string $raw = 'Closure';
    public mixed $variableBase;
    public BaseMethods $method;
    public ?ParamsNode $params = null;
    public self $type;
    public bool $overrideVariableFocus = false;
    public bool $generateNewVariable = false;
    public function __construct(public Token $token)
    {
        $this->type = $this;
        return parent::__construct($token);
    }

    public function getRawType(): string
    {
        return $this->raw;
    }
}
