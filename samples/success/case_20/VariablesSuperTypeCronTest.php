<?php

use PHPUnit\Framework\TestCase;

class VariablesSuperTypeCronTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesSuperTypeCron.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }

    public function testCronIsNormalisedToUppercase(): void
    {
        $this->assertIsString($this->vars['variables']);
        $this->assertEquals('@DAILY', $this->vars['variables']);
    }

    public function testCronIsAValidMacro(): void
    {
        $validMacros = ['@YEARLY', '@ANNUALLY', '@MONTHLY', '@WEEKLY', '@DAILY', '@HOURLY', '@REBOOT'];
        $this->assertContains($this->vars['variables'], $validMacros);
    }

    public function testReferenceMatchesOriginal(): void
    {
        $this->assertSame($this->vars['variables'], $this->vars['variablesReference']);
    }
}
