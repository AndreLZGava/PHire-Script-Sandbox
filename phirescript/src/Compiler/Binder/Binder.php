<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder;

use PHireScript\Compiler\Binder as AstBinder;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;

interface Binder
{
    public function mustBind(Node $node): bool;
    public function bind(Node $node, AstBinder $binder): void;
}
