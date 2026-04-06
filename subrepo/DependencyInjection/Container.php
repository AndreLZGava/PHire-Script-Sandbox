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

    // Registra uma classe como Transient (nova instância a cada chamada)
    public function addTransient(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'transient');
    }

    // Registra uma classe como Singleton (mesma instância para toda a aplicação)
    public function addSingleton(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'singleton');
    }

    // Registra uma classe como Scoped (mesma instância dentro do mesmo escopo)
    public function addScoped(string $abstract, string $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'scoped');
    }

    private function bind(string $abstract, ?string $concrete, string $lifetime): void
    {
        // Se a classe concreta não for informada, ela é a própria classe abstrata
        $concrete = $concrete ?? $abstract;

        // As definições são sempre salvas no container raiz (Root)
        $targetContainer = $this->getRoot();
        $targetContainer->bindings[$abstract] = [
            'concrete' => $concrete,
            'lifetime' => $lifetime
        ];
    }

    // Cria um novo escopo isolado
    public function createScope(): Container
    {
        return new Container($this);
    }

    public function get(string $abstract)
    {
        $root = $this->getRoot();

        // Verifica se a dependência está registrada
        if (!isset($root->bindings[$abstract])) {
            // Tenta resolver automaticamente mesmo se não foi registrada previamente
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

    // Usa Reflection para instanciar a classe e suas dependências
    private function resolve(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Classe {$concrete} não encontrada.");
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("A classe {$concrete} não pode ser instanciada.");
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
                throw new Exception("Não é possível resolver dependências de tipos primitivos na classe {$concrete}.");
            }

            // Pede ao container para resolver a dependência recursivamente
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
