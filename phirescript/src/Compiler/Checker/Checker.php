<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Checker;

use PHireScript\Compiler\Checker as CompilerChecker;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;

abstract class Checker {
    abstract public function mustCheck(Node $node): bool;
    abstract public function check(Node $node, CompilerChecker $checker): void;

    protected function willCheck(array $subforCheck, CompilerChecker $checker): void {
        foreach ($checker->checkers as $check) {
            foreach ($subforCheck as $statements) {
                if ($check->mustCheck($statements)) {
                    $check->check($statements, $checker);
                }
            }
        }
    }
}
