<?php

namespace Sandbox\Samples\error;

use PHireScript\Compiler;
use PHireScript\Core\CompileMode;
use PHireScript\Core\CompilerContext;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\ModeTest;

class ErrorMode extends ModeTest
{
    public function before(AbstractCaseValidation $abstractCase)
    {
        $config = [
            "dev" => true,
            "namespace" => "PHireScript\Sandbox",
            "currency" => "USD",
            "resolver" => "custom",
            "paths" => [
                "source" => "src/output",
                "dist" => "src/compiled",
                "test" => "src/tests"
            ],

            "generated_at" => "2026-01-16 00:31:33"
        ];

        $abstractCase->orchestrator->config->setModified(json_encode($config, JSON_PRETTY_PRINT));
        $abstractCase->before();
    }

    public function execute(AbstractCaseValidation $abstractCase)
    {
        try {
            $context = new CompilerContext(CompileMode::BUILD);

            $compiler = new Compiler($context);
            $compiler->compile();
            $abstractCase->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function rightAfterFirstExecution(AbstractCaseValidation $abstractCase)
    {
        $abstractCase->rightAfterFirstExecution();
    }

    public function executeAgain(AbstractCaseValidation $abstractCase)
    {
        $abstractCase->executeAgain();
    }

    public function after(AbstractCaseValidation $abstractCase)
    {
        $abstractCase->after();
    }

    public function executeTest(AbstractCaseValidation $abstractCase)
    {
        $abstractCase->executeTest();
    }
}
