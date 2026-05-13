<?php

use PHPUnit\Framework\TestCase;

class VariablesIntTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesInt.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertArrayHasKey('variables2', $this->vars);
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }

    public function testIntLiteral(): void
    {
        $this->assertIsInt($this->vars['variables']);
        $this->assertEquals(12, $this->vars['variables']);
    }

    public function testIntCastFromString(): void
    {
        $this->assertIsInt($this->vars['variables2']);
        $this->assertEquals(13, $this->vars['variables2']);
    }

    public function testIntReference(): void
    {
        $this->assertIsInt($this->vars['variablesReference']);
        $this->assertSame($this->vars['variables'], $this->vars['variablesReference']);
    }
}
