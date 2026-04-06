<?php

use PHPUnit\Framework\TestCase;

class VariablesTest extends TestCase
{
    public function testCompiledMathOperations()
    {
        require __DIR__ . '/../../Compiled/Variables/Variables.php';

        $this->assertIsFloat($price);
        $this->assertIsFloat($income);
        $this->assertIsFloat($total);
        $this->assertIsFloat($sub);
        $this->assertIsFloat($add);
        $this->assertIsFloat($division);
        $this->assertIsFloat($complex);


        $this->assertEquals(19.9, $price);
        $this->assertEquals(1.05, $income);


        $this->assertEquals($price, $override);
        $this->assertEquals(19.9 * 1.05, $total);
        $this->assertEquals(19.9 - 1.05, $sub);
        $this->assertEquals(19.9 + 1.05, $add);
        $this->assertEquals(19.9 / 1.05, $division);


        $expectedComplex = 19.9 - (1.05 / 19.9);

        $this->assertEquals($expectedComplex, $complex);
    }
}
