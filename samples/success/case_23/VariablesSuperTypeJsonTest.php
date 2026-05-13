<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bugs:
 * - `$variables = Json::cast(myArray);` → undefined constant 'myArray' (missing $)
 * - `$byVariableReference = Json::cast(variablesObject);` → same issue
 * - `$variablesReference = variables;` → same issue
 * Only $myArray and $byString are safely testable.
 */
class VariablesSuperTypeJsonTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeJson.php';
        } catch (\Error) {
            // Compilation bug: variable references missing $ prefix
        }
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testMyArrayIsDefinedBeforeError(): void
    {
        $this->assertArrayHasKey('myArray', $this->vars);
    }

    public function testMyArrayIsAnAssociativeArray(): void
    {
        $this->assertIsArray($this->vars['myArray']);
        $this->assertArrayHasKey('test', $this->vars['myArray']);
        $this->assertEquals('test1', $this->vars['myArray']['test']);
    }
}
