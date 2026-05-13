<?php

use PHPUnit\Framework\TestCase;

class VariablesSuperTypeColorTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesSuperTypeColor.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }

    public function testColorIsNormalisedToUppercaseHex(): void
    {
        $this->assertIsString($this->vars['variables']);
        $this->assertEquals('#FFFFFF', $this->vars['variables']);
    }

    public function testColorStartsWithHash(): void
    {
        $this->assertStringStartsWith('#', $this->vars['variables']);
    }

    public function testColorHasSixHexDigits(): void
    {
        $hex = ltrim($this->vars['variables'], '#');
        $this->assertEquals(6, strlen($hex));
        $this->assertMatchesRegularExpression('/^[0-9A-F]{6}$/', $hex);
    }

    public function testReferenceMatchesOriginal(): void
    {
        $this->assertSame($this->vars['variables'], $this->vars['variablesReference']);
    }
}
