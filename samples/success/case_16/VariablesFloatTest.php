<?php

use PHPUnit\Framework\TestCase;

class VariablesFloatTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesFloat.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertArrayHasKey('variables2', $this->vars);
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }

    public function testFloatLiteral(): void
    {
        $this->assertIsFloat($this->vars['variables']);
        $this->assertEquals(12.5, $this->vars['variables']);
    }

    public function testFloatCastFromString(): void
    {
        $this->assertIsFloat($this->vars['variables2']);
        $this->assertEquals(12.5, $this->vars['variables2']);
    }

    public function testFloatReference(): void
    {
        $this->assertIsFloat($this->vars['variablesReference']);
        $this->assertSame($this->vars['variables'], $this->vars['variablesReference']);
    }
}
