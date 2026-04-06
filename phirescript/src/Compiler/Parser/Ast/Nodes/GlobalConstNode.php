<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class GlobalConstNode extends Expression {
  public string $value;
  public function __construct(
    public Token $token,
  ) {
    $this->value = $token->value;
  }
}
