<?php

use PHPUnit\Framework\TestCase;

class VariablesStringTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/VariablesString.php';
            return get_defined_vars();
        })();
    }

    public function testLiteralString(): void
    {
        $this->assertSame('this is a string', $this->vars['variables']);
    }

    public function testStringCast(): void
    {
        $this->assertSame('12.02', $this->vars['variables2']);
    }

    public function testReferenceVariable(): void
    {
        $this->assertSame($this->vars['variables'], $this->vars['variablesReference']);
    }

    public function testJoinResult(): void
    {
        $this->assertSame('this is a string meu teste', $this->vars['myVariable']);
    }

    public function testGetTypeReturnsString(): void
    {
        $this->assertSame('string', $this->vars['getType']);
    }
}
