<?php

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\SuperTypes\Uuid;

class CompiledUuidTest extends TestCase
{
    public function testUuidGenerationAndReference()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesSuperTypeUuid.php';

        $this->assertTrue(isset($generatedUuid));

        $this->assertMatchesRegularExpression(
            '/^[0-9a-fA-F-]{36}$/',
            (string) $generatedUuid
        );
        $this->assertSame($generatedUuid, $variablesReference);
    }
}
