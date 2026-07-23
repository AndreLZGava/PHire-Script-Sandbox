<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;

class NamedParamsBasicTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = file_get_contents(__DIR__ . '/NamedParamsBasic.php');
    }

    public function test_named_arg_resolves_to_positional_order(): void
    {
        $this->assertStringContainsString('\str_repeat($text, 3)', $this->output);
    }

    public function test_emitted_php_is_syntactically_valid(): void
    {
        $result = shell_exec('php -l ' . escapeshellarg(__DIR__ . '/NamedParamsBasic.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $result);
    }
}
