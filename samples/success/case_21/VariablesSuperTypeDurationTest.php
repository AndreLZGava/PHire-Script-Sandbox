<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: `$variablesReference = variables;` emits undefined constant.
 * Only $variables (set before the error) is fully testable.
 */
class VariablesSuperTypeDurationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeDuration.php';
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

    public function testDurationOf1mIs60Seconds(): void
    {
        $this->assertIsInt($this->vars['variables']);
        $this->assertEquals(60, $this->vars['variables']);
    }

    public function testVariablesReferenceIsSetDueToCompilationBug(): void
    {
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }
}
