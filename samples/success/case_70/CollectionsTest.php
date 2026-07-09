<?php

use PHPUnit\Framework\TestCase;

class CollectionsTest extends TestCase
{
    private array $vars = [];

    protected function setUp(): void
    {
        $this->vars = (function () {
            require __DIR__ . '/Collections.php';
            return get_defined_vars();
        })();
    }

    public function testMapCompilesAsEmptyArray(): void
    {
        $this->assertIsArray($this->vars['myFirst']);
        $this->assertEmpty($this->vars['myFirst']);
    }

    public function testQueueCompilesAsEmptyArray(): void
    {
        $this->assertIsArray($this->vars['myQueue2']);
        $this->assertEmpty($this->vars['myQueue2']);
    }

    public function testStackCompilesAsEmptyArray(): void
    {
        $this->assertIsArray($this->vars['myStack']);
        $this->assertEmpty($this->vars['myStack']);
    }

    public function testListCompilesAsEmptyArray(): void
    {
        $this->assertIsArray($this->vars['myList']);
        $this->assertEmpty($this->vars['myList']);
    }

    public function testArrayLiteralInitiallyHasTwoElements(): void
    {
        $this->assertContains('test', $this->vars['myArray']);
        $this->assertContains('teste', $this->vars['myArray']);
    }

    public function testAddAppendsValueInPlace(): void
    {
        $this->assertContains('params', $this->vars['myArray']);
    }

    public function testAddReturnsAppendedValue(): void
    {
        $this->assertSame('params', $this->vars['myNew']);
    }
}
