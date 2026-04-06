<?php

use PHPUnit\Framework\TestCase;

class PrimitivesTest extends TestCase
{
    public function testCompiledVariablesTypesAndValues()
    {
        require __DIR__ . '/../../Compiled/Variables/Primitives.php';

      // --- Strings ---
        $this->assertIsString($userName);
        $this->assertIsString($idAsString);

        $this->assertEquals("André", $userName);
        $this->assertEquals('12345', $idAsString);

      // --- Integers ---
        $this->assertIsInt($userAge);
        $this->assertIsInt($ageFromText);

        $this->assertEquals(25, $userAge);
        $this->assertEquals(30, $ageFromText);

      // --- Float ---
        $this->assertIsFloat($productPrice);
        $this->assertIsFloat($taxValue);

        $this->assertEquals(250.99, $productPrice);
        $this->assertEquals(0.15, $taxValue);

      // --- Bool ---
        $this->assertIsBool($isUserActive);
        $this->assertIsBool($statusFromBinary);

        $this->assertTrue($isUserActive);
        $this->assertTrue($statusFromBinary);

      // --- Arrays ---
        $this->assertIsArray($techStack);
        $this->assertIsArray($singleItemArray);

        $this->assertCount(3, $techStack);
        $this->assertEquals(["PHP", "PS", "TS"], $techStack);

        $this->assertCount(1, $singleItemArray);
        $this->assertEquals([$userName], $singleItemArray);

      // --- Objects ---
        $this->assertIsObject($dataContainer);
        $this->assertIsObject($myObject);
        $this->assertIsObject($objFromMap);

        $this->assertEquals(1, $dataContainer->id);
        $this->assertEquals("test", $myObject->test);
        $this->assertEquals(1, $objFromMap->id);
    }
}
