<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Counter.php';
require_once __DIR__ . '/Labeled.php';
require_once __DIR__ . '/ValueHolder.php';

class Case59Test extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    public function testCounterGetCountReturnsInitialValue(): void
    {
        $fqcn = $this->ns . '\Counter';
        $counter = new $fqcn(5);
        $this->assertEquals(5, $counter->getCount());
    }

    public function testCounterSetCountChangesValue(): void
    {
        $fqcn = $this->ns . '\Counter';
        $counter = new $fqcn(5);
        $counter->setCount(10);
        $this->assertEquals(10, $counter->getCount());
    }

    public function testLabeledIsATrait(): void
    {
        $reflection = new \ReflectionClass($this->ns . '\Labeled');
        $this->assertTrue($reflection->isTrait());
    }

    public function testLabeledHasGetLabelMethod(): void
    {
        $reflection = new \ReflectionClass($this->ns . '\Labeled');
        $this->assertTrue($reflection->hasMethod('getLabel'));
    }

    public function testValueHolderGetValueReturnsInitialValue(): void
    {
        $fqcn = $this->ns . '\ValueHolder';
        $holder = new $fqcn('hello');
        $this->assertEquals('hello', $holder->getValue());
    }

    public function testValueHolderConstructorSetsValue(): void
    {
        $fqcn = $this->ns . '\ValueHolder';
        $holder = new $fqcn('world');
        $this->assertEquals('world', $holder->getValue());
    }
}
