<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;

class NamedParamsSplitTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = file_get_contents(__DIR__ . '/NamedParamsSplit.php');
    }

    public function test_named_separator_resolves_to_first_positional(): void
    {
        $this->assertStringContainsString('\explode(\'-\', $text', $this->output);
    }

    public function test_emitted_php_is_syntactically_valid(): void
    {
        $result = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/NamedParamsSplit.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $result);
    }
}
