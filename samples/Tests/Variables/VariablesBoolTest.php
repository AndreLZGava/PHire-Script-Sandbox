<?php

use PHPUnit\Framework\TestCase;

class VariablesBoolTest extends TestCase
{
    public function testCompiledBoolVariables()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesBool.php';

        $this->assertIsBool($varBool);
        $this->assertIsBool($varBool2);
        $this->assertIsBool($varBoolReference);

        $this->assertTrue($varBool);
        $this->assertFalse($varBool2);

        $this->assertSame($varBool, $varBoolReference);
    }
}
