<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

abstract class Node
{
    public int $line;
    public int $column;
    public function __construct(public Token $token)
    {
        $this->line = $token->line;
        $this->column = $token->column;
    }
}
