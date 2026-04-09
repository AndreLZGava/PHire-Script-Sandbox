<?php

declare(strict_types=1);

namespace PHireScript;

use Exception;
use PHireScript\Compiler\Binder;
use PHireScript\Compiler\Checker;
use PHireScript\Compiler\Emitter;
use PHireScript\Compiler\Parser;
use PHireScript\Compiler\Processors\PhpFileGeneratorHandler;
use PHireScript\Compiler\Processors\PreprocessorInterface;
use PHireScript\Compiler\Scanner;
use PHireScript\Compiler\Validator;
use PHireScript\Core\CompilerContext;
use PHireScript\Helper\Debug\Debug;
use Symfony\Component\DependencyInjection\Compiler\Compiler;

class Transpiler implements TranspilerInterface
{
    private readonly PreprocessorInterface $generator;
    private string $codeBeforeGenerator;

    public function __construct(
        private readonly array $config,
        private DependencyGraphBuilder $dependencyManager,
        private CompilerContext $context,
    ) {
        $this->generator = new PhpFileGeneratorHandler(false);
    }

    public function compile(string $code, string $path): string
    {
        $this->codeBeforeGenerator = '';
        $scanner = new Scanner($code, $path);
        $tokens = $scanner->tokenize();

        //Debug::show($tokens);exit;
        $validator = new Validator();
        $validator->validate($tokens);

        $parser = new Parser($this->config, $this->dependencyManager, $this->context);
        $ast = $parser->parse($tokens, $path);
        // Debug::show($ast); exit;
        $symbolTable = new SymbolTable();
        $binder = new Binder($symbolTable);
        $updatedAst = $binder->bind($ast);

        $checker = new Checker();
        $checker->check($updatedAst, $symbolTable);

        //Debug::show($updatedAst);exit;
        $emitter = new Emitter($this->config, $this->dependencyManager);
        $preCompiledPhpCode = $emitter->emit($updatedAst);

        $this->codeBeforeGenerator = $preCompiledPhpCode;
        // /Debug::show($preCompiledPhpCode);exit;
        $result = $this->generator->process($preCompiledPhpCode);
        return $result;
    }

    public function getCodeBeforeGenerator(): string
    {
        return $this->codeBeforeGenerator ?? '';
    }
}
