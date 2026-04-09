<?php

declare(strict_types=1);

namespace PHireScript\Compiler\Processors;

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class PhpFileGeneratorHandler implements PreprocessorInterface
{
    private $parser;
    private $printer;
    public function __construct(private readonly bool $strictDebugMode = false)
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->printer = new PrettyPrinter\Standard();
    }
    public function process(string $code): string
    {
        try {
            $ast = $this->parser->parse($code);

            $symbolTable = new \PHireScript\SymbolTable();

            $collector = new \PhpParser\NodeTraverser();
            $collector->addVisitor(new \PHireScript\Visitor\TypeCollector($symbolTable));
            $ast = $collector->traverse($ast);

            $traverser = new \PhpParser\NodeTraverser();
            //$traverser->addVisitor(new \PHireScript\Visitor\VariableResolver($symbolTable));
            $traverser->addVisitor(new \PHireScript\Visitor\StringObjectTransformer($symbolTable));
            $traverser->addVisitor(new \PHireScript\Visitor\ArrayObjectTransformer($symbolTable));

            $ast = $traverser->traverse($ast);

            return $this->printer->prettyPrintFile($ast);
        } catch (\PhpParser\Error $error) {
            if (!$this->strictDebugMode) {
                throw new \Exception("Compilation error: " . $error->getMessage());
            }
            throw new \Exception("Compilation error: " . $error->getMessage());
        }
    }
}
