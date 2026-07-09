<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Getter.php';

class GetterTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\Getter';

    private function make(int $id = 1, string $name = 'Alice', bool $active = true): object
    {
        return new $this->fqcn($id, $name, $active);
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $getter = $this->make(42, 'Alice', true);
        $this->assertEquals(42, $getter->getId());
    }

    public function testGetNameReturnsCorrectValue(): void
    {
        $getter = $this->make(1, 'Bob', false);
        $this->assertEquals('Bob', $getter->getName());
    }

    public function testGetActiveReturnsTrueWhenActive(): void
    {
        $getter = $this->make(1, 'Alice', true);
        $this->assertTrue($getter->getActive());
    }

    public function testGetActiveReturnsFalseWhenInactive(): void
    {
        $getter = $this->make(1, 'Alice', false);
        $this->assertFalse($getter->getActive());
    }

    public function testConstructorSetsAllFields(): void
    {
        $getter = $this->make(99, 'Carol', true);
        $this->assertEquals(99, $getter->getId());
        $this->assertEquals('Carol', $getter->getName());
        $this->assertTrue($getter->getActive());
    }
}
