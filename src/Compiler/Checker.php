<?php

declare(strict_types=1);

namespace PHireScript\Compiler;

use Exception;
use PHireScript\Compiler\Checker\Declaration\Class\MagicMethodsChecker;
use PHireScript\Compiler\Checker\Declaration\ClassChecker;
use PHireScript\Compiler\Checker\Expression\MethodConsumptionChecker;
use PHireScript\Compiler\Checker\Root\ProgramChecker;
use PHireScript\Compiler\Checker\Expression\Types\QueueChecker;
use PHireScript\SymbolTable;
use PHireScript\Compiler\Parser\Ast\Nodes\ClassNode;
use PHireScript\Compiler\Parser\Ast\Nodes\MethodDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\Exceptions\CheckerException;

/**
 * @todo implement that interface may not have properties and
 * methods must always be public
 */
class Checker
{
    private $table;
    public array $checkers = [];
    public function __construct()
    {
        $this->checkers = [
            new QueueChecker(),
            new MethodConsumptionChecker(),
            new MagicMethodsChecker(),
            new ProgramChecker(),
            new ClassChecker(),
        ];
    }

    public function check(Program $ast, SymbolTable $table)
    {
        foreach ($this->checkers as $check) {
            if ($check->mustCheck($ast)) {
                $check->check($ast, $this);
            }
        }


        $this->table = $table;
        foreach ($ast->statements as $node) {
            if ($node instanceof ClassNode) {
                $this->checkClassBody($node);
            }
        }
    }

    private function checkClassBody($classNode)
    {
        foreach ($classNode->body as $member) {
            if ($member instanceof PropertyNode) {
                $propertyName = $member->name;
                if ($member->defaultValue !== null) {
                    // $this->ensureTypeCompatibility($member, $member->defaultValue);
                }

                if ($classNode->readOnly && $member->defaultValue) {
                    throw new \Exception(
                        "Semantic error in property '{$propertyName}': " .
                            "Readonly classes or immutable object its not" .
                            " allowed to define a default value!"
                    );
                }

                if (
                    !\in_array('abstract', $classNode->modifiers) &&
                    \in_array('abstract', $member->modifiers)
                ) {
                    throw new \Exception(
                        "Semantic error in property '{$propertyName}': " .
                            "Abstract properties are allowed only in abstract classes"
                    );
                }
            }

            if ($member instanceof MethodDeclarationNode) {
                $this->validateMethodReturn($member);
            }
        }
    }

    private function validateMethodReturn(MethodDeclarationNode $method)
    {
        $this->ensureReturnsForMethods($method);

        foreach ($method->bodyCode as $node) {
            if ($node instanceof \PHireScript\Compiler\Parser\Ast\Nodes\ReturnNode) {
                $this->checkTypeCompatibility($method->returnType, $node->expression, $method->name);
            }
        }
    }

    private function checkTypeCompatibility($declaredType, $expressionNode, $methodName)
    {
        if ($expressionNode instanceof \PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode) {
            if ($declaredType === 'Array') {
                return true;
            }

            if (\str_starts_with((string) $declaredType, '[') && \str_ends_with((string) $declaredType, ']')) {
                $innerTypes = \trim((string) $declaredType, '[]');
                $allowedTypes = \explode('|', $innerTypes);

                foreach ($expressionNode->elements as $index => $element) {
                    $elementType = $this->getNodeType($element);

                    if (!\in_array($elementType, $allowedTypes, true)) {
                        throw new \Exception(
                            "Semantic Error in method '{$methodName}': " .
                                "Element at index {$index} is of type '{$elementType}', " .
                                "but the return array only accepts [" . \implode('|', $allowedTypes) . "]."
                        );
                    }
                }
                return true;
            }
        }

        return true;
    }

    private function ensureReturnsForMethods(MethodDeclarationNode $prop)
    {
        $returnMethod = \explode('|', (string) $prop->returnType);
        if (
            $prop->mustBeBool && \count($returnMethod) > 1 ||
            $prop->mustBeBool && \current($returnMethod) !== 'Bool'
        ) {
            throw new CheckerException('Method ' . $prop->name .
                '? must return exclusively "Bool". Passed "' .
                $prop->returnType . '"!', $prop->line, $prop->column);
        }

        if (
            $prop->mustBeVoid && \count($returnMethod) > 1 ||
            $prop->mustBeVoid && \current($returnMethod) !== 'Void'
        ) {
            throw new CheckerException('Method ' . $prop->name .
                '! must return exclusively "Void". Passed "' .
                $prop->returnType . '"!', $prop->line, $prop->column);
        }
    }


    private function ensureTypeCompatibility(PropertyNode $prop, $valueNode)
    {
        $isValid = false;

        foreach ($prop->resolvedTypeInfo as $typeInfo) {
            if ($this->isCompatible($typeInfo, $valueNode)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new \Exception("Semantic Error: Property '{$prop->name}' does not support the assigned type.");
        }
    }

    private function isCompatible(array $typeInfo, $valueNode): bool
    {
        return match ($typeInfo['category']) {
            'primitive' => $this->checkPrimitive($typeInfo['native'], $valueNode),
            'supertype' => $this->checkPrimitive('string', $valueNode),
            'metatype' => true,
            'custom' => true,
            default => false,
        };
    }

    private function checkPrimitive(string $nativeType, $valueNode): bool
    {
        $nodeType = $this->getNodeType($valueNode);

        if ($nodeType === 'unknown') {
            return true;
        }

        return match ($nativeType) {
            'string' => $nodeType === 'String',
            'int'    => $nodeType === 'Int',
            'float'  => $nodeType === 'Float',
            'bool'   => $nodeType === 'Bool',
            'array'  => $nodeType === 'Array',
            'object' => $nodeType === 'Object' || $nodeType === 'Custom',
            default  => false
        };
    }


    private function getNodeType($node): string
    {

        if ($node instanceof \PHireScript\Compiler\Parser\Ast\Nodes\LiteralNode) {
            return $node->rawType;
        }

        if ($node instanceof \PHireScript\Compiler\Parser\Ast\Nodes\ArrayLiteralNode) {
            return 'Array';
        }

        if ($node instanceof \PHireScript\Compiler\Parser\Ast\Nodes\VariableDeclarationNode) {
            return $this->table->getType($node->name, $node->line);
        }

        return 'unknown';
    }
}
