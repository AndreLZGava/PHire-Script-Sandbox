<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Color;

class VariablesColorTest extends TestCase
{
    public function testCompiledColorSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeColor.php';

        $this->assertIsString($variables);
        $this->assertIsString($variablesReference);

        $this->assertNotInstanceOf(Color::class, $variables);

        $this->assertEquals('#FFFFFF', $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
