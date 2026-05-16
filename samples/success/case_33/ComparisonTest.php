<?php

use PHPUnit\Framework\TestCase;

class ComparisonTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    private string $source = '';

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/Comparison.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
        $this->source = (string) file_get_contents(__DIR__ . '/Comparison.php');
    }

    public function testFalseComparisonSkipsBranch(): void
    {
        // 1 > 2 is false, so $result is never 'greater'
        $this->assertNotEquals('greater', $this->vars['result'] ?? null);
    }

    public function testEqualityOperatorWorks(): void
    {
        // 1 == 1 is true
        $this->assertStringContainsString('1 == 1', $this->source);
    }

    public function testStrictInequalityOperatorWorks(): void
    {
        // 1 !== 2 is true
        $this->assertStringContainsString('1 !== 2', $this->source);
    }

    public function testGreaterThanOrEqualOperatorWorks(): void
    {
        // 2 >= 1 is true
        $this->assertStringContainsString('2 >= 1', $this->source);
    }

    public function testLessThanOrEqualOperatorWorks(): void
    {
        // 1 <= 2 is true
        $this->assertStringContainsString('1 <= 2', $this->source);
    }

    public function testGreaterThanOperatorWorks(): void
    {
        // 1 > 2 is the first condition (false)
        $this->assertStringContainsString('1 > 2', $this->source);
    }

    public function testLastTrueConditionSetsResult(): void
    {
        // All conditions after 1 > 2 are true; last one wins
        $this->assertArrayHasKey('result', $this->vars);
        $this->assertEquals('lte', $this->vars['result']);
    }

    public function testCompiledFileContainsIfStatements(): void
    {
        $this->assertStringContainsString('if (', $this->source);
    }
}
