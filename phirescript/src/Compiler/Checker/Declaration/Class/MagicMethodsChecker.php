<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Checker\Declaration\Class;

use Exception;
use PHireScript\Compiler\Checker as CompilerChecker;
use PHireScript\Compiler\Checker\Checker;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\Node;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CompileException;

class MagicMethodsChecker extends Checker {
    public function mustCheck(Node $node): bool {
        return $node instanceof MethodDeclarationNode && $node->token->isMagicMethod();
    }

    public function check(Node $node, CompilerChecker $checker): void {
        $this->validateParamsAndReturnType($node);
        return;
    }

    private function validateParamsAndReturnType($node): void {
        /** @var MethodDeclarationNode $node */
        $implements = $node->implements;

        $declaredReturnTypes = array_map('strtolower', $node->returnType->types ?? []);
        $expectedReturnTypes = array_map('strtolower', $implements->return ?? []);

        sort($declaredReturnTypes);
        sort($expectedReturnTypes);

        if ($declaredReturnTypes !== $expectedReturnTypes) {
            throw new CompileException(
                "Magic method '{$node->name}' has invalid return type. Expected: " .
                    implode('|', $implements->return) .
                    ", got: " .
                    implode('|', $node->returnType->types ?? []),
                    $node->token->line,
                    $node->token->column,
            );
        }


        $declaredParams = $node->parameters->params ?? [];
        $expectedParams = $implements->params ?? [];

        if (count($expectedParams) === 1 && $expectedParams[0]->name === '@params') {
            return;
        }

        if (count($declaredParams) !== count($expectedParams)) {
            throw new CompileException(
                "Magic method '{$node->name}' expects " . count($expectedParams) .
                    " parameters, got " . count($declaredParams),
                    $node->token->line,
                    $node->token->column,
            );
        }

        foreach ($declaredParams as $index => $param) {
            $expected = $expectedParams[$index];

            $declaredTypes = array_map('strtolower', $param->types ?? []);
            $expectedTypes = is_array($expected->type)
                ? array_map('strtolower', $expected->type)
                : [strtolower($expected->type)];

            sort($declaredTypes);
            sort($expectedTypes);

            if ($declaredTypes !== $expectedTypes) {
                throw new CompileException(
                    "Invalid type for parameter \${$param->name} in magic method '{$node->name}'. " .
                        "Expected: " . implode('|', $expectedTypes) .
                        ", got: " . implode('|', $declaredTypes),
                        $node->token->line,
                        $node->token->column,
                );
            }
        }
    }
}
