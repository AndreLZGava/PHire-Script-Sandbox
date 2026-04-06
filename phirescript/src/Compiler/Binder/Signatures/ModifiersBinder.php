<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Binder\Signatures;

use PHireScript\Compiler\Binder as CompilerBinder;
use PHireScript\Compiler\Binder\Binder;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Helper\Debug\Debug;

class ModifiersBinder implements Binder
{
    public function mustBind(Node $node): bool
    {
        return isset($node->modifiers) && !empty($node->modifiers);
    }

    public function bind(Node $node, CompilerBinder $binder): void
    {
        $modifiers = $node->modifiers;
        $map = [
            '*' => 'public',
            '+' => 'protected',
            '#' => 'private',
        ];

        $allowed = ['public', 'protected', 'private', 'static', 'abstract', 'readonly'];

        $result = [];

        foreach ($modifiers as $item) {
            if (isset($map[$item])) {
                $item = $map[$item];
            }

            if (\in_array($item, $allowed, true)) {
                $result[] = $item;
            }
        }

        $node->modifiers = \array_values(\array_unique($result));
    }
}
