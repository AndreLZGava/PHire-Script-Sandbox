<?php

declare(strict_types=1);

namespace PHireScript\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Arg;
use PhpParser\Node\Name;
use PhpParser\Node\Identifier;
use PHireScript\SymbolTable;

class ArrayObjectTransformer extends NodeVisitorAbstract
{
    private $map = [
    'push'    => 'array_push',
    'pop'     => '\array_key_exists',
    'shift'   => 'array_shift',
    'unshift' => 'array_unshift',
    'keys'    => 'array_keys',
    'values'  => 'array_values',
    'count'   => 'count',
    ];

    public function __construct(private readonly SymbolTable $symbolTable)
    {
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof \PhpParser\Node\Stmt\Function_ ||
            $node instanceof \PhpParser\Node\Expr\Closure ||
            $node instanceof \PhpParser\Node\Expr\ArrowFunction
        ) {
            $this->symbolTable->enterScope();
        }
        return null;
    }

    public function leaveNode(Node $node)
    {
        if (
            $node instanceof \PhpParser\Node\Stmt\Function_ ||
            $node instanceof \PhpParser\Node\Expr\Closure ||
            $node instanceof \PhpParser\Node\Expr\ArrowFunction
        ) {
            $this->symbolTable->exitScope();
            return $node;
        }

        if ($node instanceof \PhpParser\Node\Stmt\Expression) {
            if (
                $node->expr instanceof MethodCall &&
                $node->expr->name instanceof Identifier
            ) {
                $methodName = $node->expr->name->toString();

                if ($methodName === 'each') {
                    $methodCall = $node->expr;
                    $arg = $methodCall->args[0]->value;

                    if (
                        $arg instanceof \PhpParser\Node\Expr\Closure ||
                        $arg instanceof \PhpParser\Node\Expr\ArrowFunction
                    ) {
                        $params = $arg->params;

                        $valVar = isset($params[0]) ?
                        $params[0]->var :
                        new \PhpParser\Node\Expr\Variable('val');
                        $keyVar = isset($params[1]) ? $params[1]->var : null;

                        $stmts = $arg instanceof \PhpParser\Node\Expr\ArrowFunction
                        ? [new \PhpParser\Node\Stmt\Expression($arg->expr)]
                        : $arg->stmts;

                        return new \PhpParser\Node\Stmt\Foreach_(
                            $methodCall->var,
                            $valVar,
                            [
                            'keyVar' => $keyVar,
                            'stmts'  => $stmts
                            ]
                        );
                    }
                }
            }
        }

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $methodName = $node->name->toString();

            if ($node->var instanceof \PhpParser\Node\Expr\Variable) {
                $varName = $node->var->name;
                $type = $this->symbolTable
                ->getType($varName, $node->var->getStartLine());

                if ($type !== 'ARRAY' && $type !== 'UNKNOWN' && $type !== null) {
                    return null;
                }
            }

            if ($methodName === 'join') {
                return new FuncCall(
                    new Name('\implode'),
                    [
                    $node->args[0] ?? new Arg(new \PhpParser\Node\Scalar\String_("")),
                    new Arg($node->var)
                    ]
                );
            }

            if ($methodName === 'map') {
                return new FuncCall(
                    new Name('array_map'),
                    [
                    $node->args[0],
                    new Arg($node->var)
                    ]
                );
            }

            if (isset($this->map[$methodName])) {
                $phpFunction = $this->map[$methodName];
                $args = [new Arg($node->var)];
                foreach ($node->args as $arg) {
                    $args[] = $arg;
                }
                return new FuncCall(new Name($phpFunction), $args);
            }
        }

        return null;
    }
}
