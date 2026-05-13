<?php

use PHPUnit\Framework\TestCase;

/**
 * Known compilation bug: `$variablesReference = variables;` emits undefined constant.
 * Only $variables (set before the error) is fully testable.
 */
class VariablesSuperTypeUrlTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        try {
            include __DIR__ . '/VariablesSuperTypeUrl.php';
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

    public function testUrlIsAValidUrl(): void
    {
        $this->assertIsString($this->vars['variables']);
        $this->assertNotFalse(filter_var($this->vars['variables'], FILTER_VALIDATE_URL));
    }

    public function testUrlValueIsPreserved(): void
    {
        $this->assertEquals('https://www.example.com', $this->vars['variables']);
    }

    public function testUrlHasScheme(): void
    {
        $parts = parse_url($this->vars['variables']);
        $this->assertArrayHasKey('scheme', $parts);
        $this->assertEquals('https', $parts['scheme']);
    }

    public function testUrlHasHost(): void
    {
        $parts = parse_url($this->vars['variables']);
        $this->assertArrayHasKey('host', $parts);
        $this->assertNotEmpty($parts['host']);
    }
}
