<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Json;

class VariablesSuperTypeJsonTest extends TestCase
{
    public function testCompiledJsonSuperType()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeJson.php';

        $this->assertIsArray($variables);
        $this->assertIsArray($variablesReference);

        $this->assertIsArray($byVariableReference);
        $this->assertIsArray($byString);

        $this->assertEquals(['test' => 'test1'], $variables);

        $this->assertSame($variables, $variablesReference);
    }
}
