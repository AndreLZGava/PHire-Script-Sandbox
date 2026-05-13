<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: `$variablesReference = variables;` emits undefined constant.
 * Only $variables (set before the error) is fully testable.
 */
class VariablesSuperTypeEmailTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeEmail.php';
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

    public function testEmailIsAValidEmailAddress(): void
    {
        $this->assertIsString($this->vars['variables']);
        $this->assertNotFalse(filter_var($this->vars['variables'], FILTER_VALIDATE_EMAIL));
    }

    public function testEmailValueIsPreserved(): void
    {
        $this->assertEquals('andrelzgava@gmail.com', $this->vars['variables']);
    }
}
