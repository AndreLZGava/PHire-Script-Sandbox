<?php

namespace Sandbox\Samples\success;

use PHireScript\Compiler;
use PHireScript\Core\CompileMode;
use PHireScript\Core\CompilerContext;
use PHireScript\Orchestrator\AbstractCaseValidation;
use PHireScript\Orchestrator\ModeTest;

class SuccessMode extends ModeTest
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
            ob_start();
            $context = new CompilerContext(CompileMode::BUILD);

            $compiler = new Compiler($context);
            $compiler->compile();
            $output = ob_get_clean();

            restore_exception_handler();

            $abstractCase->setOutput($output);
            $abstractCase->execute();
        } catch (\Exception $e) {
            ob_end_clean();
            restore_exception_handler();
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
        $this->syncInternalsToOutput();
        $abstractCase->executeTest();
    }

    private function syncInternalsToOutput(): void
    {
        $src  = __DIR__ . '/../../src/compiled/Internal';
        $dest = __DIR__ . '/../../src/output/Internal';

        if (!is_dir($src)) {
            return;
        }

        $this->copyDir($src, $dest);
    }

    private function copyDir(string $from, string $to): void
    {
        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        foreach (new \FilesystemIterator($from) as $item) {
            $target = $to . '/' . $item->getFilename();
            if ($item->isDir()) {
                $this->copyDir($item->getPathname(), $target);
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }
}
