<?php

use PHPUnit\Framework\TestCase;

class VariablesBoolTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesBool.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('varBool', $this->vars);
        $this->assertArrayHasKey('varBool2', $this->vars);
        $this->assertArrayHasKey('varBoolReference', $this->vars);
    }

    public function testVarBoolLiteralIsTrue(): void
    {
        $this->assertIsBool($this->vars['varBool']);
        $this->assertTrue($this->vars['varBool']);
    }

    public function testVarBool2CastFromZeroStringIsFalse(): void
    {
        $this->assertIsBool($this->vars['varBool2']);
        $this->assertFalse($this->vars['varBool2']);
    }

    public function testVarBoolReferenceMatchesVarBool(): void
    {
        $this->assertIsBool($this->vars['varBoolReference']);
        $this->assertSame($this->vars['varBool'], $this->vars['varBoolReference']);
    }
}
