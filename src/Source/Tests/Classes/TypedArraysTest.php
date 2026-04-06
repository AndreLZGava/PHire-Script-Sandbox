<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Compiled/Classes/TypedArrays.php';

use PHireScript\Classes\TypedArrays;

class TypedArraysTest extends TestCase
{
    public function testSimpleArrayReturnsArray()
    {
        $typed = new TypedArrays();

        $result = $typed->testSimpleArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testPrimitiveArrayReturnsValidatedArray()
    {
        $typed = new TypedArrays();

        $result = $typed->testPrimitiveArray('anything');

        $this->assertIsArray($result);

        $this->assertEquals([1, 15.2, 'test'], $result);
    }

    public function testPrimitiveArrayStructure()
    {
        $typed = new TypedArrays();

        $result = $typed->testPrimitiveArray('anything');

        $this->assertIsInt($result[0]);
        $this->assertIsFloat($result[1]);
        $this->assertIsString($result[2]);
    }
}
