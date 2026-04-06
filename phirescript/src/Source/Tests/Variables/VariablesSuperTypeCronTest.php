<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Cron;

class VariablesSuperTypeCronTest extends TestCase
{
    public function testCompiledCronSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeCron.php';

        $this->assertIsString($variables);
        $this->assertIsString($variablesReference);

        $this->assertNotInstanceOf(Cron::class, $variables);

        $this->assertEquals('@DAILY', $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
