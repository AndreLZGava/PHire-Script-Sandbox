<?php

use PHPUnit\Framework\TestCase;

class ArithmeticTest extends TestCase
{
    private object $obj;

    protected function setUp(): void
    {
        require_once __DIR__ . '/Arithmetic.php';
        $this->obj = new \PHireScript\Sandbox\src\output\Arithmetic(10.0, 2.0, 7, 1);
    }

    private function invoke(string $method): mixed
    {
        $r = new \ReflectionMethod($this->obj, $method);
        $r->setAccessible(true);
        return $r->invoke($this->obj);
    }

    public function testGetTotal(): void
    {
        $this->assertEqualsWithDelta(12.0, $this->invoke('getTotal'), 0.001);
    }

    public function testGetMultiplied(): void
    {
        $this->assertEqualsWithDelta(11.0, $this->invoke('getMultiplied'), 0.001);
    }

    public function testGetSubtracted(): void
    {
        $this->assertEqualsWithDelta(8.0, $this->invoke('getSubtracted'), 0.001);
    }

    public function testGetDivided(): void
    {
        $this->assertEqualsWithDelta(5.0, $this->invoke('getDivided'), 0.001);
    }

    public function testGetMod(): void
    {
        $this->assertEquals(1, $this->invoke('getMod'));
    }

    public function testGetPower(): void
    {
        $this->assertEqualsWithDelta(100.0, $this->invoke('getPower'), 0.001);
    }
}
