<?php

use PHPUnit\Framework\TestCase;

class ConstantsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/Constants.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testTestVariableIsDefined(): void
    {
        $this->assertArrayHasKey('test', $this->vars);
    }

    public function testTestVariableEqualsEError(): void
    {
        $this->assertEquals(E_ERROR, $this->vars['test']);
    }

    public function testTestVariableIsInt(): void
    {
        $this->assertIsInt($this->vars['test']);
    }

    public function testTestVariableValueIsOne(): void
    {
        $this->assertSame(1, $this->vars['test']);
    }
}
