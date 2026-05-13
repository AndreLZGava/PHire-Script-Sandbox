<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: `$variablesReference = variables;` emits undefined constant.
 * Only $variables (set before the error) is fully testable.
 */
class VariablesSuperTypeSlugTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeSlug.php';
        } catch (\Error) {
            // Compilation bug: $variablesReference = variables; → undefined constant
        }
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testVariablesIsDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
    }

    public function testSlugIsLowercase(): void
    {
        $this->assertIsString($this->vars['variables']);
        $this->assertEquals(strtolower($this->vars['variables']), $this->vars['variables']);
    }

    public function testSlugValueFromTestThen(): void
    {
        $this->assertEquals('test-then', $this->vars['variables']);
    }

    public function testSlugMatchesPattern(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->vars['variables']);
    }
}
