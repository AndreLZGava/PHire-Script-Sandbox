<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Url;

class VariablesSuperTypeUrlTest extends TestCase
{
    public function testCompiledUrlSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeUrl.php';

        $this->assertIsString($variables);
        $this->assertIsString($variablesReference);

        $this->assertNotInstanceOf(Url::class, $variables);

        $this->assertEquals('https://www.example.com', $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
