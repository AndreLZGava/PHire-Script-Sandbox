<?php

namespace PHireScript\Compiler\DependencyGraphBuilder;

class Node
{
    public string $package;
    public string $file;
    public array $dependsOn;
    public bool $dirty = false;

    public function __construct(
        string $package,
        string $file,
        public $namespace,
        array $dependsOn = [],
    ) {
        $this->package = $package;
        $this->file = $file;
        $this->dependsOn = $dependsOn;
    }
}
