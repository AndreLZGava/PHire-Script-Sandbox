<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    public function testTotalMultipliesBaseByRate(): void
    {
        $calc = new Calculator(10, 2.5);
        $this->assertSame(25.0, $calc->total());
    }

    public function testWithBonusAddsBasePlusTenTimesRate(): void
    {
        $calc = new Calculator(10, 2.0);
        $this->assertSame(40.0, $calc->withBonus());
    }

    public function testGetBaseReturnsInt(): void
    {
        $calc = new Calculator(7, 1.0);
        $this->assertIsInt($calc->getBase());
        $this->assertSame(7, $calc->getBase());
    }

    public function testGetRateReturnsFloat(): void
    {
        $calc = new Calculator(5, 3.14);
        $this->assertIsFloat($calc->getRate());
        $this->assertSame(3.14, $calc->getRate());
    }
}
