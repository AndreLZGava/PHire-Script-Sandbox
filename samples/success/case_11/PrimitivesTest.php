<?php

use PHPUnit\Framework\TestCase;

class PrimitivesTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/Primitives.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testStringInference(): void
    {
        $this->assertIsString($this->vars['userName']);
        $this->assertEquals('André', $this->vars['userName']);
    }

    public function testStringCast(): void
    {
        $this->assertIsString($this->vars['idAsString']);
        $this->assertEquals('12345', $this->vars['idAsString']);
    }

    public function testIntInference(): void
    {
        $this->assertIsInt($this->vars['userAge']);
        $this->assertEquals(25, $this->vars['userAge']);
    }

    public function testIntCast(): void
    {
        $this->assertIsInt($this->vars['ageFromText']);
        $this->assertEquals(30, $this->vars['ageFromText']);
    }

    public function testFloatInference(): void
    {
        $this->assertIsFloat($this->vars['productPrice']);
        $this->assertEquals(250.99, $this->vars['productPrice']);
    }

    public function testFloatCast(): void
    {
        $this->assertIsFloat($this->vars['taxValue']);
        $this->assertEquals(0.15, $this->vars['taxValue']);
    }

    public function testBoolInference(): void
    {
        $this->assertIsBool($this->vars['isUserActive']);
        $this->assertTrue($this->vars['isUserActive']);
    }

    public function testBoolCast(): void
    {
        $this->assertIsBool($this->vars['statusFromBinary']);
        $this->assertTrue($this->vars['statusFromBinary']);
    }

    public function testArrayInference(): void
    {
        $this->assertIsArray($this->vars['techStack']);
        $this->assertCount(3, $this->vars['techStack']);
        $this->assertContains('PHP', $this->vars['techStack']);
        $this->assertContains('PS', $this->vars['techStack']);
        $this->assertContains('TS', $this->vars['techStack']);
    }

    public function testArrayCast(): void
    {
        $this->assertIsArray($this->vars['singleItemArray']);
        $this->assertCount(1, $this->vars['singleItemArray']);
        $this->assertContains('André', $this->vars['singleItemArray']);
    }

    public function testObjectInference(): void
    {
        $this->assertIsObject($this->vars['dataContainer']);
        $this->assertEquals(1, $this->vars['dataContainer']->id);
    }

    public function testObjectLiteral(): void
    {
        $this->assertIsObject($this->vars['myObject']);
        $this->assertEquals('test', $this->vars['myObject']->test);
    }

    public function testObjectCast(): void
    {
        $this->assertIsObject($this->vars['objFromMap']);
        $this->assertEquals(1, $this->vars['objFromMap']->id);
    }

    public function testAllVariablesAreDefined(): void
    {
        $expected = [
            'userName', 'idAsString', 'userAge', 'ageFromText',
            'productPrice', 'taxValue', 'isUserActive', 'statusFromBinary',
            'techStack', 'singleItemArray', 'dataContainer', 'myObject', 'objFromMap',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $this->vars, "Variable \${$key} is not defined");
        }
    }
}
