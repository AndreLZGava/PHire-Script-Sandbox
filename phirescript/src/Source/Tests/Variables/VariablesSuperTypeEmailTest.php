<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Email;

class VariablesSuperTypeEmailTest extends TestCase
{
    public function testCompiledEmailSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeEmail.php';

        $this->assertIsString($variables);
        $this->assertIsString($variablesReference);

        $this->assertNotInstanceOf(Email::class, $variables);

        $this->assertEquals('andrelzgava@gmail.com', $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
