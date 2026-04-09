<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;
use PHireScript\Runtime\CustomClasses\MagicBaseMethods;
use PHireScript\Runtime\CustomClasses\MagicMethods;

class MethodDeclarationNode extends Node
{
    public function __construct(
        public Token $token,
        public string $name,
        public ?MethodScopeNode $bodyCode = null,
        public array $modifiers = [],
        public ?ParamsListNode $parameters = null,
        public ?ReturnTypeNode $returnType = null,
        public bool $mustBeBool = false,
        public bool $mustBeVoid = false,
        public ?MagicBaseMethods $implements = null,
    ) {
    }
}
