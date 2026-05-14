<?php

use PHPUnit\Framework\TestCase;

class IfElseTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/IfElse.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testElseBranchExecutesWhenConditionIsFalse(): void
    {
        $this->assertArrayHasKey('myVar', $this->vars);
    }

    public function testElseBranchAssignsCorrectValue(): void
    {
        $this->assertEquals('else', $this->vars['myVar']);
    }

    public function testIfBranchDoesNotAssignItsValue(): void
    {
        $this->assertNotEquals('if', $this->vars['myVar']);
    }
}
