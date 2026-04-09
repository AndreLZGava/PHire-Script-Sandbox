<?php

declare(strict_types=1);

namespace PHireScript;

use PHireScript\Compiler\DependencyGraphBuilder\DependencyTree\Parser;
use PHireScript\Compiler\Program;
use PHireScript\Compiler\Scanner;
use PHireScript\Core\CompilerContext;

class TranspilerDependencyTree {
    public function __construct(
        private readonly array $config,
        private CompilerContext $context,
    ) {
    }

    public function compile(string $code, string $path): Program {
        $scanner = new Scanner($code, $path);
        $tokens = $scanner->tokenize();

        $parser = new Parser(
            $this->config,
            new DependencyGraphBuilder(),
            $this->context,
        );
        $ast = $parser->parse($tokens, $path);

        return $ast;
    }

    public function getCodeBeforeGenerator(): string {
        return $this->codeBeforeGenerator ?? '';
    }
}
