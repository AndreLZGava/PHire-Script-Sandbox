<?php

use PHPUnit\Framework\TestCase;

class VariablesObjectTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/VariablesObject.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testAllVariablesAreDefined(): void
    {
        $this->assertArrayHasKey('variables', $this->vars);
        $this->assertArrayHasKey('variables2', $this->vars);
        $this->assertArrayHasKey('variables3', $this->vars);
        $this->assertArrayHasKey('variablesReference', $this->vars);
    }

    public function testVariablesIsEmptyObject(): void
    {
        $this->assertIsObject($this->vars['variables']);
        $this->assertEmpty(get_object_vars($this->vars['variables']));
    }

    public function testVariables2IsCastedObject(): void
    {
        $obj = $this->vars['variables2'];
        $this->assertIsObject($obj);
        $this->assertEquals('this was an array', $obj->array);
        $this->assertEquals(['new test'], $obj->test);
    }

    public function testVariables3IsInlineObjectWithAllProperties(): void
    {
        $obj = $this->vars['variables3'];
        $this->assertIsObject($obj);
        $this->assertEquals(1, $obj->test);
        $this->assertEquals('Example', $obj->name);
        $this->assertIsObject($obj->anotherReference);
    }

    public function testVariables3AnotherReferencePointsToVariables2(): void
    {
        $this->assertEquals(
            $this->vars['variables2'],
            $this->vars['variables3']->anotherReference
        );
    }

    public function testVariablesReferenceIsTheSameAsVariables(): void
    {
        $this->assertIsObject($this->vars['variablesReference']);
        $this->assertEquals($this->vars['variables'], $this->vars['variablesReference']);
    }
}
