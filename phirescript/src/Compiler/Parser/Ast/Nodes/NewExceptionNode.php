<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes;

use PHireScript\Compiler\Parser\Managers\Token\Token;

class NewExceptionNode extends Expression
{
    public function __construct(
        Token $token,
        public string $className,
        public string $message,
    ) {
    }
}
