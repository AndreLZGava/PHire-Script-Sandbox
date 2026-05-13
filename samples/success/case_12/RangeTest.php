<?php

use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/Range.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testMyTestIsDefined(): void
    {
        $this->assertArrayHasKey('myTest', $this->vars);
    }

    public function testMyTestIsArray(): void
    {
        $this->assertIsArray($this->vars['myTest']);
    }

    public function testMyTestHasCorrectCount(): void
    {
        // [1, 2] + range(1,10) = 10 + 50 + range(2,-10) = 13 → 26 total
        $this->assertCount(26, $this->vars['myTest']);
    }

    public function testMyTestStartsWithOneAndTwo(): void
    {
        $arr = array_values($this->vars['myTest']);
        $this->assertEquals(1, $arr[0]);
        $this->assertEquals(2, $arr[1]);
    }

    public function testMyTestContainsAscendingRange(): void
    {
        // range(1,10) spread after the first two elements
        $arr = array_values($this->vars['myTest']);
        $this->assertEquals(1, $arr[2]);
        $this->assertEquals(10, $arr[11]);
    }

    public function testMyTestContains50(): void
    {
        $this->assertContains(50, $this->vars['myTest']);
    }

    public function testMyTestContainsDescendingRange(): void
    {
        // range(2,-10) last segment, includes negative values
        $this->assertContains(0, $this->vars['myTest']);
        $this->assertContains(-10, $this->vars['myTest']);
    }

    public function testMyTestContainsNegativeValues(): void
    {
        $negatives = array_filter($this->vars['myTest'], fn($v) => $v < 0);
        $this->assertNotEmpty($negatives);
    }
}
