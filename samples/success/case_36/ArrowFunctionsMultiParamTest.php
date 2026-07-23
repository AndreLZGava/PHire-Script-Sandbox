<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionsMultiParamTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctionsMultiParam.php';
            return get_defined_vars();
        })();
    }

    public function testAddIsCallable(): void
    {
        $this->assertArrayHasKey('add', $this->vars);
        $this->assertIsCallable($this->vars['add']);
    }

    public function testAddReturnsFirstArgument(): void
    {
        $add = $this->vars['add'];
        $this->assertSame(3, $add(3, 7));
    }

    public function testGreetUserIsCallable(): void
    {
        $this->assertArrayHasKey('greetUser', $this->vars);
        $this->assertIsCallable($this->vars['greetUser']);
    }

    public function testGreetUserReturnsName(): void
    {
        $greetUser = $this->vars['greetUser'];
        $this->assertSame('Alice', $greetUser('Alice', 30));
    }

    public function testFormatValueIsCallable(): void
    {
        $this->assertArrayHasKey('formatValue', $this->vars);
        $this->assertIsCallable($this->vars['formatValue']);
    }

    public function testFormatValueReturnsPassedValue(): void
    {
        $formatValue = $this->vars['formatValue'];
        $this->assertSame('hello', $formatValue('hello'));
        $this->assertSame(42, $formatValue(42));
    }
}
