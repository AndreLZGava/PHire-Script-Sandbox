<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OverrideTest.php';

class OverrideTestTest extends TestCase
{
    private string $fqcn = 'PHireScript\Sandbox\src\output\OverrideTest';

    private function make(int $id): object
    {
        return new $this->fqcn($id);
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $obj = $this->make(42);
        $this->assertEquals(42, $obj->getId());
    }

    public function testConstructorSetsId(): void
    {
        $obj = $this->make(99);
        $this->assertEquals(99, $obj->getId());
    }

    public function testClassExistsInCorrectNamespace(): void
    {
        $this->assertTrue(class_exists($this->fqcn));
    }
}
