<?php

use PHPUnit\Framework\TestCase;

class VariablesIntTest extends TestCase
{
    public function testCompiledIntVariables()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesInt.php';

        $this->assertIsInt($variables);
        $this->assertIsInt($variables2);
        $this->assertIsInt($variablesReference);

        $this->assertEquals(12, $variables);
        $this->assertEquals(13, $variables2);

        $this->assertSame($variables, $variablesReference);
    }
}
