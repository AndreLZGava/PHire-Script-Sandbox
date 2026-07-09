<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Counter.php';

class CounterTest extends TestCase
{
    private string $ns = 'PHireScript\Sandbox\src\output';

    private function makeCounter(int $count, string $label = ''): object
    {
        $fqcn = $this->ns . '\Counter';
        return new $fqcn($count, $label);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists($this->ns . '\Counter'));
    }

    public function testGetCountReturnsCount(): void
    {
        $counter = $this->makeCounter(7);
        $this->assertEquals(7, $counter->getCount());
    }

    public function testGetLabelReturnsLabel(): void
    {
        $counter = $this->makeCounter(0, 'hello');
        $this->assertEquals('hello', $counter->getLabel());
    }

    public function testHasCountReturnsFalseWhenZero(): void
    {
        $counter = $this->makeCounter(0);
        $this->assertFalse($counter->hasCount());
    }

    public function testHasCountReturnsTrueWhenNonZero(): void
    {
        $counter = $this->makeCounter(5);
        $this->assertTrue($counter->hasCount());
    }

    public function testResetCountDoesNotThrow(): void
    {
        $counter = $this->makeCounter(3);
        $counter->resetCount();
        $this->assertEquals(3, $counter->getCount());
    }
}
