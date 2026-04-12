<?php

namespace PHireScript\Orchestrator;

use PHireScript\Helper\Debug\Debug;

abstract class AbstractCaseValidation
{
    protected bool $stopIfNoTest = false;
    protected string $output = '';

    public function __construct(public Orchestrator $orchestrator) {}

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function before() {}

    public function execute() {}

    public function rightAfterFirstExecution() {}

    function executeAgain() {}

    public function after() {}



    public function assertHasMessage(array $expected)
    {
        $output = $this->getOutput();

        $lines = array_filter(
            array_map(fn($line) => $this->normalize($line), explode("\n", $output))
        );

        foreach ($expected as $expectedLine) {
            $normalizedExpected = $this->normalize($expectedLine);

            $found = false;

            foreach ($lines as $line) {
                if ($line === $normalizedExpected) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \Exception(
                    "Expected line not found:\n{$expectedLine}\n\nGot:\n" . implode("\n", $lines)
                );
            }
        }
    }

    private function stripAnsi(string $text): string
    {
        return preg_replace('/\e\[[0-9;]*m/', '', $text);
    }

    private function normalize(string $text): string
    {
        $text = $this->stripAnsi($text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function executeTest()
    {
        $command = 'vendor/bin/phpunit --colors=never';

        exec($command, $output, $exitCode);

        $result = implode("\n", $output);

        $this->renderTestResult($result, $exitCode);
    }

    private function renderTestResult(string $output, int $exitCode): void
    {
        $cleanOutput = preg_replace('/\e\[[0-9;]*m/', '', $output);

        if ($exitCode === 0) {
            if (str_contains($cleanOutput, 'No tests executed')) {
                if ($this->stopIfNoTest) {
                    echo "\n\033[1;33m✖ Stopped cause its has no tests, and its required!\033[0m\n";
                    exit;
                }
                echo "\n\033[1;33m✖ No tests executed\033[0m\n";
            }
            return;
        }

        echo "\n\033[1;31m✖ Tests failed\033[0m\n";

        $this->highlightFailures($cleanOutput);

        throw new \Exception("Test suite failed");
    }

    private function highlightFailures(string $output): void
    {
        echo "\n Failures summary:\n\n";
        Debug::show($output);
        foreach (explode("\n", $output) as $line) {
            if (
                str_contains($line, 'FAILURES') ||
                str_contains($line, 'Failed asserting') ||
                str_contains($line, 'Error')
            ) {
                echo "\033[1;31m{$line}\033[0m\n";
            }
        }
    }
}
