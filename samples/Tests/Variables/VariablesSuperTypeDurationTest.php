<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Duration;

class VariablesSuperTypeDurationTest extends TestCase
{
    public function testCompiledDurationSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeDuration.php';

        $this->assertIsInt($variables);
        $this->assertIsInt($variablesReference);

        $this->assertNotInstanceOf(Duration::class, $variables);

        $this->assertEquals(60, $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
