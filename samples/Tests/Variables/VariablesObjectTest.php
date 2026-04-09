<?php

use PHPUnit\Framework\TestCase;

class VariablesObjectTest extends TestCase
{
    public function testCompiledObjectVariables()
    {
        require __DIR__ . '/../../Compiled/Variables/VariablesObject.php';

        $this->assertIsObject($variables);
        $this->assertIsObject($variables2);
        $this->assertIsObject($variables3);
        $this->assertIsObject($variablesReference);

        $this->assertEquals(new stdClass(), $variables);

        $this->assertTrue(property_exists($variables2, 'array'));
        $this->assertEquals('this was an array', $variables2->array);

        $this->assertTrue(property_exists($variables3, 'test'));
        $this->assertEquals(1, $variables3->test);

        $this->assertSame($variables, $variablesReference);
    }
}
