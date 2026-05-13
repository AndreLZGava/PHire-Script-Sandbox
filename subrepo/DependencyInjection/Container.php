<?php

class Container
{
    private array $bindings = [];
    private array $singletons = [];
    private array $scopedInstances = [];
    private ?Container $parent = null;

    public function __construct(?Container $parent = null)
    {
        $this->parent = $parent;
    }

    // Registers a class as Transient (new instance on every call)
    public function addTransient(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'transient');
    }

    // Registers a class as Singleton (same instance across the entire application)
    public function addSingleton(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'singleton');
    }

    // Registers a class as Scoped (same instance within the same scope)
    public function addScoped(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'scoped');
    }

    private function bind(string $abstract, ?string $concrete, string $lifetime): void
    {
        // If no concrete class is given, the abstract class itself is used
        $concrete = $concrete ?? $abstract;

        // Definitions are always stored on the root container
        $targetContainer = $this->getRoot();
        $targetContainer->bindings[$abstract] = [
            'concrete' => $concrete,
            'lifetime' => $lifetime
        ];
    }

    // Creates a new isolated scope
    public function createScope(): Container
    {
        return new Container($this);
    }

    public function get(string $abstract)
    {
        $root = $this->getRoot();

        // Checks if the dependency is registered
        if (!isset($root->bindings[$abstract])) {
            // Attempts to resolve automatically even if not previously registered
            return $this->resolve($abstract);
        }

        $binding = $root->bindings[$abstract];
        $lifetime = $binding['lifetime'];
        $concrete = $binding['concrete'];

        if ($lifetime === 'singleton') {
            if (!isset($root->singletons[$abstract])) {
                $root->singletons[$abstract] = $this->resolve($concrete);
            }
            return $root->singletons[$abstract];
        }

        if ($lifetime === 'scoped') {
            if (!isset($this->scopedInstances[$abstract])) {
                $this->scopedInstances[$abstract] = $this->resolve($concrete);
            }
            return $this->scopedInstances[$abstract];
        }

        // Se for transient
        return $this->resolve($concrete);
    }

    // Uses Reflection to instantiate the class and its dependencies
    private function resolve(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Class {$concrete} not found.");
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} cannot be instantiated.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                throw new Exception("Cannot resolve primitive type dependencies in class {$concrete}.");
            }

            // Asks the container to resolve the dependency recursively
            $dependencies[] = $this->get($type->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    private function getRoot(): Container
    {
        $current = $this;
        while ($current->parent !== null) {
            $current = $current->parent;
        }
        return $current;
    }
}
