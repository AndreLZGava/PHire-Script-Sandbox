<?php

use PHPUnit\Framework\TestCase;

class TryHandleAlwaysTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/TryHandleAlways.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testTryBlockSetsVariable(): void
    {
        $this->assertArrayHasKey('variable', $this->vars);
        $this->assertEquals('test', $this->vars['variable']);
    }

    public function testFinallyBlockAlwaysRuns(): void
    {
        $this->assertArrayHasKey('variable3', $this->vars);
        $this->assertEquals('always', $this->vars['variable3']);
    }

    public function testCatchBlockDoesNotRunOnHappyPath(): void
    {
        // No exception is thrown, so the handle block should not execute
        $this->assertArrayNotHasKey('variable2', $this->vars);
    }

    public function testVariableIsString(): void
    {
        $this->assertIsString($this->vars['variable']);
    }

    public function testVariable3IsString(): void
    {
        $this->assertIsString($this->vars['variable3']);
    }

    public function testTryHandleAlwaysMapsToPhpTryCatchFinally(): void
    {
        $source = file_get_contents(__DIR__ . '/TryHandleAlways.php');
        $this->assertStringContainsString('try', $source);
        $this->assertStringContainsString('catch', $source);
        $this->assertStringContainsString('finally', $source);
    }
}
