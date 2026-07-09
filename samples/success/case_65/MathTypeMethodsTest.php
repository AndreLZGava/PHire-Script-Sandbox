<?php

use PHPUnit\Framework\TestCase;

class MathTypeMethodsTest extends TestCase
{
    private array $vars;

    protected function setUp(): void
    {
        $this->vars = (function() {
            require __DIR__ . '/MathTypeMethods.php';
            return get_defined_vars();
        })();
    }

    public function testCubeRoot(): void
    {
        $this->assertEqualsWithDelta(3.0, $this->vars['r'], 0.001);
    }

    public function testLog(): void
    {
        $this->assertEqualsWithDelta(log(27), $this->vars['l'], 0.001);
    }

    public function testLogBase2(): void
    {
        $this->assertEqualsWithDelta(log(27, 2), $this->vars['lb'], 0.001);
    }

    public function testRounded(): void
    {
        $this->assertEquals(100, $this->vars['rounded']);
    }

    public function testFloored(): void
    {
        $this->assertEquals(100, $this->vars['floored']);
    }

    public function testCeiled(): void
    {
        $this->assertEquals(100, $this->vars['ceiled']);
    }
}
