<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionDefaultsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctionDefaults.php';
            return get_defined_vars();
        })();
    }

    public function testGreetIsCallable(): void
    {
        $this->assertArrayHasKey('greet', $this->vars);
        $this->assertIsCallable($this->vars['greet']);
    }

    public function testGreetUsesDefaultWhenNoArgPassed(): void
    {
        $greet = $this->vars['greet'];
        $this->assertSame('world', $greet());
    }

    public function testGreetUsesProvidedArg(): void
    {
        $greet = $this->vars['greet'];
        $this->assertSame('PHP', $greet('PHP'));
    }

    public function testIncrementIsCallable(): void
    {
        $this->assertArrayHasKey('increment', $this->vars);
        $this->assertIsCallable($this->vars['increment']);
    }

    public function testIncrementUsesDefaultWhenNoArgPassed(): void
    {
        $increment = $this->vars['increment'];
        $this->assertSame(0, $increment());
    }

    public function testIncrementUsesProvidedArg(): void
    {
        $increment = $this->vars['increment'];
        $this->assertSame(10, $increment(10));
    }

    public function testNullableIsCallable(): void
    {
        $this->assertArrayHasKey('nullable', $this->vars);
        $this->assertIsCallable($this->vars['nullable']);
    }

    public function testNullableReturnsProvidedText(): void
    {
        $nullable = $this->vars['nullable'];
        $this->assertSame('hello', $nullable('hello'));
    }
}
