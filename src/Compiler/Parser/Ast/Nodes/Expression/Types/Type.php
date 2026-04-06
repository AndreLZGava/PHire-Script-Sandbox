<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Ast\Nodes\Expression\Types;

interface Type
{
    public function getRawType(): string;
}
