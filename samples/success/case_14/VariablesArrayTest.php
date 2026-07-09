<?php

use PHPUnit\Framework\TestCase;

class VariablesArrayTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/VariablesArray.php';
            return get_defined_vars();
        })();
    }

    public function testVariablesIsArray(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertIsArray($this->vars['variables']);
    }

    public function testVariablesHasTestKey(): void
    {
        $this->assertArrayHasKey('test', $this->vars['variables']);
    }

    public function testVariables2IsArray(): void
    {
        $this->assertArrayHasKey('variables2', $this->vars);
        $this->assertIsArray($this->vars['variables2']);
    }

    public function testVariables2ContainsTest(): void
    {
        $this->assertContains('test', $this->vars['variables2']);
    }
}
