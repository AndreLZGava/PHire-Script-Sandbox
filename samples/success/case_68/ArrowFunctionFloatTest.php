<?php

use PHPUnit\Framework\TestCase;

class ArrowFunctionFloatTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/ArrowFunctionFloat.php';
            return get_defined_vars();
        })();
    }

    public function testCalcTotalIsCallable(): void
    {
        $this->assertArrayHasKey('calcTotal', $this->vars);
        $this->assertIsCallable($this->vars['calcTotal']);
    }

    public function testCalcTotalReturnsTwelve(): void
    {
        $fn = $this->vars['calcTotal'];
        $this->assertSame(12.0, $fn(100.0, 0.15));
    }

    public function testCalcTotalAcceptsFloatArgs(): void
    {
        $fn = $this->vars['calcTotal'];
        $result = $fn(50.5, 0.1);
        $this->assertSame(12.0, $result);
    }
}
