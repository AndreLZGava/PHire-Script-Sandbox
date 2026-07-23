<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionNoThisTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctionNoThis.php';
            return get_defined_vars();
        })();
    }

    public function testDoubleIsCallable(): void
    {
        $this->assertArrayHasKey('double', $this->vars);
        $this->assertIsCallable($this->vars['double']);
    }

    public function testDoubleReturnsValue(): void
    {
        $fn = $this->vars['double'];
        $this->assertSame(5, $fn(5));
    }

    public function testScaleIsCallable(): void
    {
        $this->assertArrayHasKey('scale', $this->vars);
        $this->assertIsCallable($this->vars['scale']);
    }

    public function testScaleReturnsCapturedMultiplier(): void
    {
        $fn = $this->vars['scale'];
        $this->assertSame(3, $fn(99));
    }
}
