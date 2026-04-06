<?php

use PHPUnit\Framework\TestCase;

class VariablesStringTest extends TestCase
{
    public function testCompiledStringVariables()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesString.php';

        $this->assertIsString($variables);
        $this->assertIsString($variables2);
        $this->assertIsString($variablesReference);

        $this->assertEquals('this is a string', $variables);
        $this->assertEquals('12.02', $variables2);

        $this->assertIsNotNumeric($variables);
        $this->assertIsNumeric($variables2);
        $this->assertSame($variables, $variablesReference);
    }
}
