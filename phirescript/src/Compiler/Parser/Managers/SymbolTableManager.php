<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Parser\Managers;

use PHireScript\Compiler\Parser\Ast\Nodes\PropertyNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableDeclarationNode;
use PHireScript\Compiler\Parser\Ast\Nodes\VariableReferenceNode;
use PHireScript\Helper\Debug\Debug;
use PHireScript\Runtime\DefaultOverrideMethods\BaseMethods;

class SymbolTableManager
{
    private array $typeDefinitions = [];
    private ?string $rawType = null;
    private array $lastExecution = [];

    public function __construct()
    {
        $targetDir = __DIR__ . '/../../../../src/Runtime/DefaultOverrideMethods/Types';
        $getDefaultOverrideMethods = $this->scanAndBuildRegistry($targetDir);
        $this->typeDefinitions = $getDefaultOverrideMethods;
    }

    public function from($rawType): self
    {
        $this->rawType = $rawType;
        return $this;
    }

    public function getFunctionFromLastExecution(string $functionName, bool $mustUpdate = false): ?BaseMethods
    {
        if (empty($this->lastExecution)) {
            return null;
        }
        $allowedParams = \count($this->lastExecution) === 1 ? \current($this->lastExecution) : (
            \count($this->lastExecution) === 0 ? [] : \implode('|', $this->lastExecution)
        );

        if (
            !\array_key_exists($allowedParams . 'Methods', $this->typeDefinitions) ||
            !\array_key_exists($functionName, $this->typeDefinitions[$allowedParams . 'Methods'])
        ) {
            return null;
        }

        $function = $this->typeDefinitions[$allowedParams . 'Methods'][$functionName];
        if ($function && $mustUpdate) {
            $this->lastExecution = $function->returnOfPhpExecution;
        }
        return $function ?? null;
    }

    public function getFunction($functionName): ?BaseMethods
    {
        if (
            is_null($this->rawType) ||
            is_null($functionName) ||
            !\array_key_exists($this->rawType . 'Methods', $this->typeDefinitions)
        ) {
            return null;
        }
        $function = $this->typeDefinitions[$this->rawType . 'Methods'][$functionName] ?? null;
        if ($function) {
            $this->lastExecution = $function->returnOfPhpExecution;
        }
        return $function ?? null;
    }


    private function scanAndBuildRegistry(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new \RuntimeException("Dir not found: $directory");
        }

        $registry = [];
        $files = glob($directory . '/*.php');

        foreach ($files as $file) {
            $classesBefore = get_declared_classes();
            require_once $file;
            $classesAfter = get_declared_classes();
            $newClasses = array_diff($classesAfter, $classesBefore);

            foreach ($newClasses as $className) {
                $reflector = new \ReflectionClass($className);

                if (!$reflector->isInstantiable()) {
                    continue;
                }

                try {
                    $instance = $this->resolveAndInstantiate($reflector);
                } catch (\Throwable $e) {
                    error_log("It was not possible instantiate {$className}: " . $e->getMessage());
                    continue;
                }

                $shortName = $reflector->getShortName();
                $registry[$shortName] = [];

                $methods = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $method) {
                    $methodName = $method->getName();

                    if (str_starts_with($methodName, '__')) {
                        continue;
                    }

                    try {
                        $result = $method->invoke($instance);

                        $registry[$shortName][$result->name] = $result;
                    } catch (\Throwable $e) {
                        error_log("Error executing method {$className}::{$methodName}: " . $e->getMessage());
                    }
                }
            }
        }

        return $registry;
    }


    private function resolveAndInstantiate(\ReflectionClass $reflector): object
    {
        $constructor = $reflector->getConstructor();

        if (!$constructor || $constructor->getNumberOfParameters() === 0) {
            return $reflector->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName === 'array') {
                        $args[] = [];
                    } elseif ($typeName === 'int') {
                        $args[] = 0;
                    } elseif ($typeName === 'string') {
                        $args[] = '';
                    } elseif ($typeName === 'bool') {
                        $args[] = false;
                    } else {
                        $args[] = null;
                    }
                } else {
                    $args[] = null;
                }
            }
        }

        return $reflector->newInstanceArgs($args);
    }
}
