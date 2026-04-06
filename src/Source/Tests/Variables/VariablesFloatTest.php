<?php

use PHPUnit\Framework\TestCase;

class VariablesFloatTest extends TestCase
{
    public function testCompiledFloatVariables()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesFloat.php';

        $this->assertIsFloat($variables);
        $this->assertIsFloat($variables2);
        $this->assertIsFloat($variablesReference);

        $this->assertEqualsWithDelta(12.5, $variables, 0.00001);
        $this->assertEqualsWithDelta(12.5, $variables2, 0.00001);

        $this->assertEqualsWithDelta($variables, $variablesReference, 0.00001);
    }
}
