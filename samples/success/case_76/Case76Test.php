<?php

namespace PHireScript\Sandbox\src\output;

use PHPUnit\Framework\TestCase;

class Case76Test extends TestCase
{
    public function testPriceWithTax(): void
    {
        $p = new Pricing(100.0, 0.1);
        $this->assertEqualsWithDelta(110.0, $p->priceWithTax(), 0.001);
    }

    public function testDiscount(): void
    {
        $p = new Pricing(100.0, 0.1);
        $this->assertEqualsWithDelta(90.0, $p->discount(), 0.001);
    }

    public function testGettersReturnCorrectTypes(): void
    {
        $p = new Pricing(50.0, 0.2);
        $this->assertIsFloat($p->getPrice());
        $this->assertIsFloat($p->getTaxRate());
    }
}
