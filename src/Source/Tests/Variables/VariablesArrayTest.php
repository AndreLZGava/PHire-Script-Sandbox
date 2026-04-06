<?php

use PHPUnit\Framework\TestCase;

class VariablesArrayTest extends TestCase
{
    public function testCompiledArrayStructures()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesArray.php';

        $this->assertIsArray($variables);
        $this->assertIsArray($variables2);
        $this->assertIsArray($variablesReference);

        $this->assertArrayHasKey('test', $variables);

        $this->assertIsArray($variables['test']);
        $this->assertEquals(['array'], $variables['test']);

        $this->assertEquals(['test'], $variables2);

        $this->assertSame($variables, $variablesReference);
    }
}
