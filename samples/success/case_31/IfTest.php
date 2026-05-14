<?php

use PHPUnit\Framework\TestCase;

class IfTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $vars = [];

    protected function setUp(): void
    {
        ob_start();
        include __DIR__ . '/If.php';
        ob_end_clean();
        $this->vars = get_defined_vars();
    }

    public function testIfBodyExecutesWhenConditionIsTrue(): void
    {
        $this->assertArrayHasKey('myVar', $this->vars);
    }

    public function testIfBodyAssignsCorrectValue(): void
    {
        $this->assertEquals('test', $this->vars['myVar']);
    }
}
