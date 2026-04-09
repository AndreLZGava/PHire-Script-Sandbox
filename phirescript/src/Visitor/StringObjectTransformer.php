<?php

declare(strict_types=1);

namespace PHireScript\Visitor;

use PHireScript\SymbolTable;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Arg;
use PhpParser\Node\Name;

class StringObjectTransformer extends NodeVisitorAbstract
{
    private $map = [
        'toUpperCase' => 'strtoupper',
        'toLowerCase' => 'strtolower',
        'length'      => 'strlen',
        'trim'        => 'trim',
        'contains'    => '\str_contains',
    ];

    public function __construct(private readonly SymbolTable $symbolTable)
    {
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof MethodCall) {
            $methodName = $node->name->toString();

            if (isset($this->map[$methodName])) {
                $phpFunction = $this->map[$methodName];
                return new FuncCall(
                    new Name($phpFunction),
                    [new Arg($node->var)]
                );
            }
        }
    }
}
